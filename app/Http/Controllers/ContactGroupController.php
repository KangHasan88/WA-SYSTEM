<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\Contact;
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
            'name' => 'nullable|string|max:100'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        // Format number
        $number = preg_replace('/\D/', '', $request->number);
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        if (substr($number, 0, 2) !== '62') {
            $number = '62' . $number;
        }
        
        $contact = Contact::create([
            'group_id' => $request->group_id,
            'number' => $number,
            'name' => $request->name
        ]);
        
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
            'name' => 'nullable|string|max:100'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        
        $number = preg_replace('/\D/', '', $request->number);
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        if (substr($number, 0, 2) !== '62') {
            $number = '62' . $number;
        }
        
        $contact->update([
            'number' => $number,
            'name' => $request->name
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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        $group_id = $request->group_id;
        $file = $request->file('file');
        
        $extension = $file->getClientOriginalExtension();
        $numbers = [];
        
        if ($extension === 'csv' || $extension === 'txt') {
            $handle = fopen($file->getPathname(), 'r');
            while (($data = fgetcsv($handle)) !== false) {
                $number = trim($data[0]);
                $name = $data[1] ?? null;
                if (!empty($number)) {
                    $numbers[] = ['number' => $number, 'name' => $name];
                }
            }
            fclose($handle);
        } else {
            return back()->with('error', 'Please use CSV format for import');
        }
        
        $imported = 0;
        $failed = 0;
        
        foreach ($numbers as $item) {
            try {
                $number = preg_replace('/\D/', '', $item['number']);
                if (substr($number, 0, 1) === '0') {
                    $number = '62' . substr($number, 1);
                }
                if (substr($number, 0, 2) !== '62') {
                    $number = '62' . $number;
                }
                
                Contact::updateOrCreate(
                    ['group_id' => $group_id, 'number' => $number],
                    ['name' => $item['name']]
                );
                $imported++;
            } catch (\Exception $e) {
                $failed++;
            }
        }
        
        return redirect()->route('contact-groups.index', ['group_id' => $group_id])
            ->with('success', "Imported {$imported} contacts, {$failed} failed");
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
        $groups = ContactGroup::withCount('contacts')->orderBy('name')->get();
        return response()->json(['groups' => $groups]);
    }
    
    /**
     * Get contacts by group (API)
     */
    public function getContacts($groupId)
    {
        $group = ContactGroup::findOrFail($groupId);
        $contacts = $group->contacts()->orderBy('name')->get();
        return response()->json(['contacts' => $contacts]);
    }
}