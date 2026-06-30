<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\Contact;
use App\Services\PhoneNumberSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactGroupController extends Controller
{
    /**
     * Display a listing of groups and contacts
     */
    public function index(Request $request)
    {
        $groups = ContactGroup::withCount('contacts')->orderBy('name')->get();
        
        $selectedGroupId = $request->get('group_id', $groups->first()?->id);
        $selectedGroup = null;
        $contacts = collect();
        
        if ($selectedGroupId) {
            $selectedGroup = ContactGroup::find($selectedGroupId);
            if ($selectedGroup) {
                $contacts = $selectedGroup->contacts()->orderBy('name')->get();
            }
        }
        
        return view('contact-groups.index', compact('groups', 'selectedGroup', 'contacts'));
    }
    
    /**
     * Store a new group
     */
    public function storeGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:contact_groups,name',
            'description' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $group = ContactGroup::create([
            'name' => $request->name,
            'description' => $request->description
        ]);
        
        return response()->json(['success' => true, 'group' => $group]);
    }
    
    /**
     * Update a group
     */
    public function updateGroup(Request $request, $id)
    {
        $group = ContactGroup::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:contact_groups,name,' . $id,
            'description' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $group->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
        
        return response()->json(['success' => true, 'group' => $group]);
    }
    
    /**
     * Delete a group
     */
    public function deleteGroup($id)
    {
        $group = ContactGroup::findOrFail($id);
        $group->delete();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Store a new contact
     */
    public function storeContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:contact_groups,id',
            'number' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,blocked,unsubscribed',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $number = app(PhoneNumberSanitizer::class)->normalize($request->number);

        if ($number === null) {
            return response()->json([
                'success' => false,
                'errors' => ['number' => [PhoneNumberSanitizer::INVALID_REASON]],
            ], 422);
        }
        
        $contact = Contact::updateOrCreate(
            ['group_id' => $request->group_id, 'number' => $number],
            ['name' => $request->name, 'status' => $request->input('status', 'active')]
        );
        
        return response()->json(['success' => true, 'contact' => $contact]);
    }
    
    /**
     * Update a contact
     */
    public function updateContact(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,blocked,unsubscribed',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $number = app(PhoneNumberSanitizer::class)->normalize($request->number);

        if ($number === null) {
            return response()->json([
                'success' => false,
                'errors' => ['number' => [PhoneNumberSanitizer::INVALID_REASON]],
            ], 422);
        }

        $duplicate = Contact::where('group_id', $contact->group_id)
            ->where('number', $number)
            ->where('id', '!=', $contact->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'errors' => ['number' => ['Nomor ini sudah ada di grup yang sama.']],
            ], 422);
        }
        
        $contact->update([
            'number' => $number,
            'name' => $request->name,
            'status' => $request->input('status', $contact->status ?? 'active'),
        ]);
        
        return response()->json(['success' => true, 'contact' => $contact]);
    }
    
    /**
     * Delete a contact
     */
    public function deleteContact($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Import contacts from CSV
     */
    public function importContacts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:contact_groups,id',
            'file' => 'required|file|mimes:csv,txt'
        ], [
            'file.mimes' => 'Import kontak saat ini menerima CSV/TXT. Simpan file Excel sebagai CSV terlebih dahulu.',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $group_id = $request->group_id;
        $file = $request->file('file');
        
        $extension = $file->getClientOriginalExtension();
        $numbers = [];
        
        if (! in_array($extension, ['csv', 'txt'], true)) {
            return back()->with('error', 'Import kontak saat ini menerima CSV/TXT. Simpan file Excel sebagai CSV terlebih dahulu.');
        }

        $handle = fopen($file->getPathname(), 'r');
        while (($data = fgetcsv($handle)) !== false) {
            $number = trim($data[0] ?? '');
            $name = $data[1] ?? null;
            if (!empty($number) && strtolower($number) !== 'nomor') {
                $numbers[] = ['number' => $number, 'name' => $name];
            }
        }
        fclose($handle);
        
        $imported = 0;
        $failed = 0;
        $duplicates = 0;
        $invalid = [];
        $sanitizer = app(PhoneNumberSanitizer::class);
        $seen = [];
        
        foreach ($numbers as $item) {
            try {
                $number = $sanitizer->normalize($item['number']);

                if ($number === null) {
                    $invalid[] = $item['number'];
                    $failed++;
                    continue;
                }

                if (isset($seen[$number])) {
                    $duplicates++;
                    continue;
                }

                $seen[$number] = true;
                
                Contact::updateOrCreate(
                    ['group_id' => $group_id, 'number' => $number],
                    ['name' => $item['name'], 'status' => 'active']
                );
                $imported++;
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        $message = "Import selesai: {$imported} kontak aktif disimpan, {$duplicates} duplikat dilewati, {$failed} invalid/gagal.";

        if ($invalid !== []) {
            $message .= ' Nomor invalid contoh: ' . implode(', ', array_slice($invalid, 0, 5));
        }

        return redirect()->route('contact-groups.index', ['group_id' => $group_id])
            ->with($failed > 0 ? 'error' : 'success', $message);
    }
    
    /**
     * Export contacts to CSV
     */
    public function exportContacts($id)
    {
        $group = ContactGroup::findOrFail($id);
        $contacts = $group->contacts()->orderBy('name')->get();
        
        $filename = "contacts_{$group->name}_" . date('Y-m-d') . ".csv";
        
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['No', 'Nama', 'Nomor WhatsApp']);
        
        foreach ($contacts as $index => $contact) {
            fputcsv($handle, [
                $index + 1,
                $contact->name ?? '-',
                $contact->number
            ]);
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
    
    /**
     * Get groups list for select dropdown (API)
     */
    public function getGroups()
    {
        $groups = ContactGroup::withCount([
            'contacts' => fn ($query) => $query->where('status', 'active'),
        ])->orderBy('name')->get();
        return response()->json(['groups' => $groups]);
    }
    
    /**
     * Get contacts by group (API)
     */
    public function getContacts($groupId)
    {
        $group = ContactGroup::findOrFail($groupId);
        $contacts = $group->contacts()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        return response()->json(['contacts' => $contacts]);
    }
}
