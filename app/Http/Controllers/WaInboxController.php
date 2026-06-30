<?php

namespace App\Http\Controllers;

use App\Models\WaInbox;
use App\Models\Contact;
use App\Models\FollowUpReminder;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WaInboxController extends Controller
{
    // Webhook endpoint untuk menerima pesan dari Node.js
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        Log::info('Webhook received:', $data);
        
        // Bersihkan from_number dari @lid dan karakter aneh
        $fromNumber = $data['from'] ?? null;
        if ($fromNumber) {
            $fromNumber = preg_replace('/@.*$/', '', $fromNumber);
            $fromNumber = preg_replace('/\D/', '', $fromNumber);
            $fromNumber = $this->formatNumber($fromNumber);
        }
        
        // Cek apakah pesan sudah ada (deduplication)
        if (isset($data['messageId'])) {
            $exists = WaInbox::where('message_id', $data['messageId'])->exists();
            if ($exists) {
                return response()->json(['message' => 'Duplicate ignored'], 200);
            }
        }
        
        // Cek apakah nomor sudah ada di contacts
        $contact = Contact::where('number', $fromNumber)->first();
        $fromName = $contact ? $contact->name : ($data['from_name'] ?? null);
        
        // Prepare media data if exists
        $mediaUrl = null;
        $mediaMime = null;
        $mediaSize = null;
        $mediaFilename = null;
        $mediaThumbnail = null;
        
        if (isset($data['media']) && !empty($data['media'])) {
            $mediaUrl = $data['media']['url'] ?? null;
            $mediaMime = $data['media']['mime'] ?? null;
            $mediaSize = $data['media']['size'] ?? null;
            $mediaFilename = $data['media']['filename'] ?? null;
            $mediaThumbnail = $data['media']['thumbnail'] ?? null;
            
            Log::info('Media received:', [
                'url' => $mediaUrl,
                'mime' => $mediaMime,
                'size' => $mediaSize,
                'filename' => $mediaFilename
            ]);
        }
        
        // Simpan ke database (incoming message)
        $inbox = WaInbox::create([
            'from_number' => $fromNumber,
            'from_name' => $fromName,
            'message' => $data['message'] ?? '',
            'message_id' => $data['messageId'] ?? null,
            'type' => isset($data['hasMedia']) && $data['hasMedia'] ? 'media' : ($data['type'] ?? 'text'),
            'media_url' => $mediaUrl,
            'media_mime' => $mediaMime,
            'media_size' => $mediaSize,
            'media_filename' => $mediaFilename,
            'media_thumbnail' => $mediaThumbnail,
            'caption' => $data['caption'] ?? null,
            'direction' => 'incoming',
            'is_read' => false,
            'is_replied' => false,
            'lead_status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
            'received_at' => now(),
        ]);

        $approvalResult = $this->processApprovalReply($fromNumber, $data['message'] ?? '');
        
        return response()->json([
            'success' => true,
            'message' => 'Message stored',
            'id' => $inbox->id,
            'approval' => $approvalResult,
        ]);
    }
    
    // Tampilkan halaman inbox
    public function index()
    {
        $conversations = WaInbox::getConversations(20);
        $unreadCount = WaInbox::getUnreadCount();
        $todayFollowUps = WaInbox::getTodayFollowUps();
        
        return view('wa-inbox.index', compact('conversations', 'unreadCount', 'todayFollowUps'));
    }
    
    // Tampilkan percakapan dengan nomor tertentu
    public function conversation($number)
    {
        $messages = WaInbox::getConversationWith($number);
        $unreadCount = WaInbox::getUnreadCount();
        
        WaInbox::markConversationAsRead($number);
        
        return view('wa-inbox.conversation', compact('messages', 'number', 'unreadCount'));
    }
    
    // API: Get conversations (for AJAX)
    public function apiConversations()
    {
        $conversations = WaInbox::getConversations(50);
        $unreadCount = WaInbox::getUnreadCount();
        
        return response()->json([
            'success' => true,
            'conversations' => $conversations,
            'unread_count' => $unreadCount
        ]);
    }
    
    // API: Get messages with specific number
    public function apiMessages($number)
    {
        $messages = WaInbox::where('from_number', $number)
            ->orderBy('created_at', 'asc')
            ->paginate(50);
        
        // Add contact name
        $contact = Contact::where('number', $number)->first();
        $displayName = $contact ? $contact->name : $number;
        
        foreach ($messages as $msg) {
            $msg->display_name = $displayName;
            // Add media helper attributes
            $msg->has_media = !empty($msg->media_url);
            $msg->media_category = $msg->media_category;
            $msg->file_icon = $msg->file_icon;
            $msg->formatted_file_size = $msg->formatted_file_size;
        }
        
        WaInbox::markConversationAsRead($number);
        
        return response()->json([
            'success' => true,
            'messages' => $messages,
            'number' => $number,
            'display_name' => $displayName
        ]);
    }
    
    // API: Mark conversation as read
    public function markAsRead($number)
    {
        $updated = WaInbox::where('from_number', $number)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
            'updated' => $updated
        ]);
    }
    
    // Format number untuk reply (bersihkan dari @lid dan format ke 62xxx)
    private function formatNumber($number): string
    {
        $number = preg_replace('/@.*$/', '', $number);
        $cleanNumber = preg_replace('/\D/', '', $number);
        
        if (empty($cleanNumber)) {
            return $number;
        }
        
        if (preg_match('/^62[0-9]{9,12}$/', $cleanNumber)) {
            return $cleanNumber;
        }
        
        if (substr($cleanNumber, 0, 1) === '0') {
            $cleanNumber = '62' . substr($cleanNumber, 1);
        } elseif (substr($cleanNumber, 0, 2) !== '62') {
            $cleanNumber = '62' . $cleanNumber;
        }
        
        while (substr($cleanNumber, 0, 4) === '6262') {
            $cleanNumber = '62' . substr($cleanNumber, 4);
        }
        
        return $cleanNumber;
    }

    private function processApprovalReply(?string $fromNumber, string $message): ?array
    {
        if (!$fromNumber) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', strtoupper($message)));

        if (!preg_match('/^(YES|NO)\s+(\d{6})$/', $text, $matches)) {
            return null;
        }

        $decision = $matches[1];
        $code = $matches[2];
        $formattedNumber = $this->formatNumber($fromNumber);

        $approval = ApprovalRequest::where('code', $code)
            ->where('to_number', $formattedNumber)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$approval) {
            Log::warning('Approval reply ignored: no matching pending approval', [
                'from_number' => $formattedNumber,
                'code' => $code,
                'decision' => $decision,
            ]);

            return [
                'matched' => false,
                'reason' => 'not_found',
                'code' => $code,
            ];
        }

        if ($approval->expires_at->isPast()) {
            $approval->update(['status' => 'expired']);

            Log::warning('Approval reply ignored: approval expired', [
                'approval_id' => $approval->approval_id,
                'from_number' => $formattedNumber,
                'code' => $code,
            ]);

            return [
                'matched' => true,
                'approval_id' => $approval->approval_id,
                'status' => 'expired',
            ];
        }

        $status = $decision === 'YES' ? 'approved' : 'rejected';
        $approval->update([
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'rejected_at' => $status === 'rejected' ? now() : null,
        ]);

        Log::info('Approval reply processed', [
            'approval_id' => $approval->approval_id,
            'from_number' => $formattedNumber,
            'code' => $code,
            'status' => $status,
        ]);

        return [
            'matched' => true,
            'approval_id' => $approval->approval_id,
            'status' => $status,
        ];
    }
    
    // Reply to message via API (with media support)
    public function reply(Request $request, $number)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'media_base64' => 'nullable|string',
            'media_mime' => 'nullable|string',
            'media_filename' => 'nullable|string|max:255'
        ]);
        
        try {
            $formattedNumber = $this->formatNumber($number);
            
            Log::info('Reply attempt', [
                'original_number' => $number,
                'formatted_number' => $formattedNumber,
                'message' => $request->message,
                'has_media' => !empty($request->media_base64),
                'media_mime' => $request->media_mime,
                'media_filename' => $request->media_filename
            ]);
            
            // Prepare payload for Node.js
            $payload = [
                'number' => $formattedNumber,
            ];
            
            if ($request->message) {
                $payload['message'] = $request->message;
            }
            
            $savedMediaPath = null;
            
            if ($request->media_base64 && $request->media_mime) {
                $payload['media_base64'] = $request->media_base64;
                $payload['media_mime'] = $request->media_mime;
                $payload['media_filename'] = $request->media_filename ?? 'file';
                
                // Save outgoing media to local storage (for history)
                $savedMediaPath = $this->saveOutgoingMedia($request->media_base64, $request->media_mime, $request->media_filename);
                Log::info('Outgoing media saved to: ' . $savedMediaPath);
            }
            
            // Use send-media endpoint if has media, otherwise use send
            $endpoint = !empty($request->media_base64) ? '/send-media' : '/send';
            $response = Http::timeout(60)->post('http://127.0.0.1:7070' . $endpoint, $payload);
            
            $result = $response->json();
            Log::info('Reply response', ['result' => $result]);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                // Get contact name for display
                $contact = Contact::where('number', $number)->first();
                $fromName = $contact ? $contact->name : 'Admin';
                
                // Simpan pesan outgoing ke database (with media if any)
                $outgoingData = [
                    'from_number' => $number,
                    'from_name' => $fromName,
                    'message' => $request->message ?? '',
                    'message_id' => 'reply_' . time() . '_' . rand(1000, 9999),
                    'type' => !empty($request->media_base64) ? 'media' : 'text',
                    'caption' => $request->message,
                    'direction' => 'outgoing',
                    'is_read' => true,
                    'is_replied' => true,
                    'received_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Add media info if exists
                if ($savedMediaPath) {
                    $outgoingData['media_url'] = $savedMediaPath;
                    $outgoingData['media_mime'] = $request->media_mime;
                    $outgoingData['media_filename'] = $request->media_filename ?? 'file';
                    $outgoingData['media_size'] = $this->getFileSize($savedMediaPath);
                }
                
                $outgoingMessage = WaInbox::create($outgoingData);
                
                Log::info('Outgoing message saved', ['id' => $outgoingMessage->id, 'media_url' => $savedMediaPath]);
                
                WaInbox::where('from_number', $number)
                    ->where('is_replied', false)
                    ->update(['is_replied' => true]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Reply sent successfully',
                    'saved' => true,
                    'outgoing_id' => $outgoingMessage->id,
                    'display_name' => $fromName,
                    'media_url' => $savedMediaPath
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send message'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Reply error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Save outgoing media to disk
    private function saveOutgoingMedia($base64Data, $mimeType, $filename)
    {
        try {
            // Create date-based folder
            $dateFolder = now()->format('Y-m-d');
            $mediaDir = storage_path('app/public/wa-media/' . $dateFolder);
            
            if (!file_exists($mediaDir)) {
                mkdir($mediaDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = $this->getExtensionFromMime($mimeType);
            }
            $safeFilename = time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filePath = $mediaDir . '/' . $safeFilename;
            
            // Decode base64 and save
            $binaryData = base64_decode($base64Data);
            file_put_contents($filePath, $binaryData);
            
            // Return public path
            return '/storage/wa-media/' . $dateFolder . '/' . $safeFilename;
            
        } catch (\Exception $e) {
            Log::error('Save outgoing media error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function getExtensionFromMime($mimeType)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'video/mp4' => 'mp4',
            'video/3gp' => '3gp',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
        ];
        
        return $extensions[$mimeType] ?? 'bin';
    }
    
    private function getFileSize($filePath)
    {
        $fullPath = public_path($filePath);
        if (file_exists($fullPath)) {
            return filesize($fullPath);
        }
        
        $storagePath = storage_path('app/public/' . str_replace('/storage/', '', $filePath));
        if (file_exists($storagePath)) {
            return filesize($storagePath);
        }
        
        return null;
    }
    
    // Download media file
    public function downloadMedia($id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            
            if (!$message->media_url) {
                return response()->json(['error' => 'No media found'], 404);
            }
            
            // Get full path to file
            $filePath = public_path($message->media_url);
            
            // Check if file exists
            if (!file_exists($filePath)) {
                // Try storage path
                $storagePath = storage_path('app/public/' . str_replace('/storage/', '', $message->media_url));
                if (file_exists($storagePath)) {
                    $filePath = $storagePath;
                } else {
                    return response()->json(['error' => 'File not found'], 404);
                }
            }
            
            $filename = $message->media_filename ?? basename($filePath);
            $mimeType = $message->media_mime ?? mime_content_type($filePath);
            
            return response()->download($filePath, $filename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Download media error: ' . $e->getMessage());
            return response()->json(['error' => 'Download failed'], 500);
        }
    }
    
    // Delete message
    public function destroy($id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            $message->delete();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // ============================================
    // SALES PIPELINE METHODS
    // ============================================
    
    // Update lead status
    public function updateLeadStatus(Request $request, $id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            $message->lead_status = $request->lead_status;
            $message->save();
            
            // If status is 'deal' or 'lost', clear follow up date
            if (in_array($request->lead_status, ['deal', 'lost'])) {
                $message->follow_up_date = null;
                $message->save();
                
                // Update reminder records
                FollowUpReminder::where('wa_inbox_id', $id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'done',
                        'done_at' => now(),
                        'done_by' => auth()->user()->name ?? 'system'
                    ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diupdate',
                'lead_status' => $message->lead_status,
                'badge' => $message->lead_status_badge
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // Update lead notes
    public function updateLeadNotes(Request $request, $id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            $message->lead_notes = $request->lead_notes;
            $message->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil disimpan',
                'lead_notes' => $message->lead_notes
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // Update follow up date
    public function updateFollowUp(Request $request, $id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            $message->follow_up_date = $request->follow_up_date;
            $message->save();
            
            // Also create reminder record
            if ($request->follow_up_date) {
                FollowUpReminder::updateOrCreate(
                    [
                        'wa_inbox_id' => $id,
                        'customer_number' => $message->from_number,
                    ],
                    [
                        'customer_name' => $message->from_name,
                        'reminder_date' => $request->follow_up_date,
                        'reminder_note' => $request->reminder_note,
                        'status' => 'pending'
                    ]
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Follow up berhasil dijadwalkan',
                'follow_up_date' => $message->follow_up_date
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // Get today's follow ups (for dashboard)
    public function getTodayFollowUps()
    {
        $followUps = WaInbox::getTodayFollowUps();
        
        return response()->json([
            'success' => true,
            'follow_ups' => $followUps,
            'count' => $followUps->count()
        ]);
    }
    
    // Mark follow up as done
    public function markFollowUpDone($id)
    {
        try {
            $message = WaInbox::findOrFail($id);
            $message->follow_up_date = null;
            $message->save();
            
            // Update reminder record
            FollowUpReminder::where('wa_inbox_id', $id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'done',
                    'done_at' => now(),
                    'done_by' => auth()->user()->name ?? 'system'
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Follow up ditandai selesai'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    // Get lead status options
    public function getLeadStatusOptions()
    {
        return response()->json([
            'success' => true,
            'statuses' => WaInbox::$leadStatuses
        ]);
    }
}
