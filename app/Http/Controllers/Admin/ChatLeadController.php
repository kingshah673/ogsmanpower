<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatLead;

class ChatLeadController extends Controller
{
    public function index(Request $request)
    {
        // AJAX count for live notifications
        if ($request->ajax_count) {
            return response()->json(ChatLead::count());
        }

        $query = ChatLead::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('assigned_to', 'like', "%{$search}%");
            });
        }

        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Date filters
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $leads = $query->latest()
                       ->paginate(30)
                       ->withQueryString();

        // Dashboard stats
        $stats = [
            'total'      => ChatLead::count(),
            'new'        => ChatLead::where('status', 'new')->count(),
            'interested' => ChatLead::where('status', 'interested')->count(),
            'closed'     => ChatLead::where('status', 'closed')->count(),
            'today'      => ChatLead::whereDate('created_at', today())->count(),
        ];

        return view(
            'backend.chatleads.index',
            compact('leads', 'stats')
        );
    }

    public function update(Request $request, $id)
    {
        $lead = ChatLead::findOrFail($id);

        $request->validate([
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|string|max:255',
            'priority' => 'nullable|string|max:50',
        ]);

        $lead->update([
            'status'      => $request->status ?? 'new',
            'notes'       => $request->notes,
            'assigned_to' => $request->assigned_to,
            'priority'    => $request->priority ?? 'medium',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Lead updated successfully.');
    }

    public function export(Request $request)
    {
        $query = ChatLead::query();

        // Same filters as index
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('assigned_to', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $leads = $query->latest()->get();

        $filename = 'chat-leads-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($leads) {

            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Phone',
                'Category',
                'Priority',
                'Assigned To',
                'Message',
                'Status',
                'Notes',
                'Created At',
            ]);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->phone,
                    $lead->category,
                    $lead->priority,
                    $lead->assigned_to,
                    $lead->message,
                    $lead->status,
                    $lead->notes,
                    $lead->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy($id)
    {
        $lead = ChatLead::findOrFail($id);
        $lead->delete();

        return redirect()
            ->back()
            ->with('success', 'Lead deleted successfully.');
    }
}