<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">Schedule Form Detail</h2>
                <p class="text-sm text-slate-500">Form #{{ $form->id }} | Version {{ $form->version }}</p>
            </div>
            <a href="{{ $isApprovalView ? route('schedules.approvals', ['location_id' => $form->location_id]) : route('schedules.index') }}" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ $isApprovalView ? 'Back to Approvals' : 'Back to Forms' }}
            </a>
        </div>
    </x-slot>

    @php
        $isFormLockedForEdit = ! $isApprovalView && in_array($form->status, ['approved', 'editing'], true) && ! $hasPendingModificationUnlock;
        $displayStatus = (! $isApprovalView && $isFormLockedForEdit)
            ? 'view'
            : $form->status;

        $formStatusStyle = match ($displayStatus) {
            'view' => 'background:#e2e8f0;color:#334155;',
            'approved' => 'background:#dcfce7;color:#15803d;',
            'rejected' => 'background:#ffe4e6;color:#be123c;',
            'partially_approved' => 'background:#dbeafe;color:#1d4ed8;',
            'editing' => 'background:#e0f2fe;color:#0369a1;',
            default => 'background:#fef3c7;color:#b45309;',
        };
        $buttonStyles = [
            'approve' => 'background:#dcfce7;color:#166534;',
            'approve_hover' => 'background:#bbf7d0;color:#166534;',
            'reject' => 'background:#fef3c7;color:#92400e;',
            'reject_hover' => 'background:#fde68a;color:#92400e;',
            'save' => 'background:#e0f2fe;color:#075985;',
            'save_hover' => 'background:#bae6fd;color:#075985;',
            'delete' => 'background:#e2e8f0;color:#1e293b;',
            'delete_hover' => 'background:#cbd5e1;color:#1e293b;',
            'approved_badge' => 'background:#dcfce7;color:#166534;',
            'rejected_badge' => 'background:#ffe4e6;color:#be123c;',
        ];
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-5">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                        <span>Total Lines: <span class="font-semibold text-slate-800">{{ $schedules->count() }}</span></span>
                        <span>Version {{ $form->version }}</span>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" style="{{ $formStatusStyle }}">
                        {{ \Illuminate\Support\Str::of($displayStatus)->replace('_', ' ')->title() }}
                    </span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Location</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $form->location->name }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $form->shift_date->format('Y-m-d') }}</p>
                    <p class="text-sm text-slate-500">{{ $form->shift_date->format('l') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted At</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $form->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted By</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $form->creator?->name ?? 'System' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Approved By</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $form->approver?->name ?? '-' }}</p>
                </div>
                </div>
            </div>

            @if ($isApprovalView)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Whole Form Decision</p>
                    @if ($canApprove)
                        <div class="flex flex-nowrap items-end gap-3 overflow-x-auto pb-1">
                            <form method="POST" action="{{ route('schedules.form.approve') }}">
                                @csrf
                                <input type="hidden" name="form_id" value="{{ $form->id }}">
                                <button type="submit" class="h-9 w-24 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['approve'] }}" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">Approve</button>
                            </form>

                            <form method="POST" action="{{ route('schedules.form.reject') }}" class="flex flex-nowrap items-end gap-2">
                                @csrf
                                <input type="hidden" name="form_id" value="{{ $form->id }}">
                                <button type="submit" class="h-9 w-24 rounded-md text-xs font-semibold transition" style="background:#ffe4e6;color:#9f1239;" onmouseover="this.style.background='#fecdd3'" onmouseout="this.style.background='#ffe4e6'">Reject</button>
                                <input type="text" name="reason" placeholder="Reason" class="h-9 w-48 rounded-md border-slate-300 text-sm" required>
                            </form>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">You can review this form, but you do not have approval permission.</p>
                    @endif
                </div>
            @endif

            @if (! $isApprovalView && $canReopenForEdit)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="mb-2 text-sm font-semibold text-amber-800">Schedule form is locked</p>
                    <p class="mb-3 text-sm text-amber-700">
                        @if ($form->status === 'editing')
                            This form has saved changes but remains locked until you press "Modify Schedule" again. After reviewing or updating the lines, submit it for re-approval.
                        @else
                            To add/cancel staff or modify times, reopen this form with a reason. After changes, submit it for re-approval.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('schedules.form.reopen') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="form_id" value="{{ $form->id }}">
                        <input type="text" name="reason" placeholder="Reason for modification" class="h-9 w-72 rounded-md border-amber-300 text-sm" required>
                        <button type="submit" class="h-9 rounded-md px-4 text-sm font-semibold transition" style="background:#d97706;color:#ffffff;" onmouseover="this.style.background='#b45309'" onmouseout="this.style.background='#d97706'">Modify Schedule</button>
                    </form>
                </div>
            @endif

            @if (! $isApprovalView && $hasPendingModificationUnlock && $form->status === 'approved')
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                    <p class="mb-2 text-sm font-semibold text-sky-800">Modification ready</p>
                    <p class="mb-3 text-sm text-sky-700">You can now add, delete, or change lines. The form will stay approved and locked in the completed list until you save a real change.</p>
                    @if ($canCancelEditing)
                        <form method="POST" action="{{ route('schedules.form.cancel-editing') }}">
                            @csrf
                            <input type="hidden" name="form_id" value="{{ $form->id }}">
                            <button type="submit" class="h-9 rounded-md px-4 text-sm font-semibold transition" style="background:#e2e8f0;color:#0f172a;" onmouseover="this.style.background='#cbd5e1'" onmouseout="this.style.background='#e2e8f0'">Cancel Modification</button>
                        </form>
                    @endif
                </div>
            @endif

            @if (! $isApprovalView && $canSubmitReapproval)
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                    <p class="mb-2 text-sm font-semibold text-sky-800">Editing in progress</p>
                    <p class="mb-3 text-sm text-sky-700">
                        @if ($hasReapprovalChanges)
                            When your changes are complete, submit this form back to the approver queue.
                        @else
                            This form is unlocked, but no changes have been made yet. You can still cancel editing and keep it locked.
                        @endif
                    </p>
                    @if ($canCancelEditing)
                        <form method="POST" action="{{ route('schedules.form.cancel-editing') }}">
                            @csrf
                            <input type="hidden" name="form_id" value="{{ $form->id }}">
                            <button type="submit" class="h-9 rounded-md px-4 text-sm font-semibold transition" style="background:#e2e8f0;color:#0f172a;" onmouseover="this.style.background='#cbd5e1'" onmouseout="this.style.background='#e2e8f0'">Cancel Editing</button>
                        </form>
                    @endif
                </div>
            @endif

            @if (! $isApprovalView && $addableStaff->isNotEmpty() && ! $isFormLockedForEdit)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="mb-3 text-sm font-semibold text-slate-700">Add Staff Line</p>
                    <form method="POST" action="{{ route('schedules.form.add-line') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="form_id" value="{{ $form->id }}">
                        <select name="user_id" class="h-9 rounded-md border-slate-300 text-sm" required>
                            <option value="">Select staff</option>
                            @foreach ($addableStaff as $staffOption)
                                <option value="{{ $staffOption->id }}">{{ $staffOption->name }}</option>
                            @endforeach
                        </select>
                        <input type="time" name="clock_in" class="h-9 rounded-md border-slate-300 text-sm" required>
                        <input type="time" name="clock_out" class="h-9 rounded-md border-slate-300 text-sm" required>
                        <input type="text" name="notes" placeholder="Notes" class="h-9 w-56 rounded-md border-slate-300 text-sm">
                        <button type="submit" class="h-9 rounded-md px-4 text-sm font-semibold transition" style="background:#0284c7;color:#ffffff;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">Add Line</button>
                    </form>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed border-collapse text-sm">
                        <thead class="bg-slate-200 text-slate-800">
                            <tr>
                                <th class="w-10 border border-slate-300 px-3 py-2 text-left font-semibold">#</th>
                                <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Staff</th>
                                <th class="w-20 border border-slate-300 px-3 py-2 text-left font-semibold">Position</th>
                                <th class="w-36 border border-slate-300 px-3 py-2 text-left font-semibold">Change</th>
                                <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Clock In</th>
                                <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Clock Out</th>
                                <th class="w-44 border border-slate-300 px-3 py-2 text-left font-semibold">Notes</th>
                                <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:nth-child(even)]:bg-slate-50">
                            @foreach ($schedules as $index => $schedule)
                                <tr class="align-top">
                                    <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-700">{{ $index + 1 }}</td>
                                    <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-900">{{ $schedule->user->name }}</td>
                                    <td class="border border-slate-200 px-3 py-3 text-slate-600">{{ $schedule->user->position?->name ?? '-' }}</td>
                                    <td class="border border-slate-200 px-3 py-3">
                                        @php
                                            $changeLabel = match ($schedule->change_type) {
                                                'added_after_approval' => 'Added',
                                                'modified_after_approval' => 'Modified',
                                                'removed_after_approval' => 'Removed',
                                                default => 'Original',
                                            };
                                            $changeStyle = match ($schedule->change_type) {
                                                'added_after_approval' => 'background:#dcfce7;color:#15803d;',
                                                'modified_after_approval' => 'background:#fef3c7;color:#b45309;',
                                                'removed_after_approval' => 'background:#ffe4e6;color:#be123c;',
                                                default => 'background:#f1f5f9;color:#334155;',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="{{ $changeStyle }}">{{ $changeLabel }}</span>
                                    </td>

                                    @if ($canManage)
                                        <td class="border border-slate-200 px-3 py-3">
                                            <input
                                                type="time"
                                                name="clock_in"
                                                form="save-line-{{ $schedule->id }}"
                                                value="{{ $schedule->starts_at->format('H:i') }}"
                                                class="h-9 w-32 rounded-md border-slate-300 text-sm"
                                                @disabled($isFormLockedForEdit)
                                            >
                                        </td>
                                        <td class="border border-slate-200 px-3 py-3">
                                            <input
                                                type="time"
                                                name="clock_out"
                                                form="save-line-{{ $schedule->id }}"
                                                value="{{ $schedule->ends_at->format('H:i') }}"
                                                class="h-9 w-32 rounded-md border-slate-300 text-sm"
                                                @disabled($isFormLockedForEdit)
                                            >
                                        </td>
                                        <td class="border border-slate-200 px-3 py-3 text-slate-700">
                                            <input
                                                type="text"
                                                value="{{ $schedule->notes ?: '-' }}"
                                                class="h-9 w-44 rounded-md border-slate-200 bg-slate-50 text-sm text-slate-600"
                                                readonly
                                            >
                                        </td>
                                        <td class="border border-slate-200 px-3 py-3">
                                            @if ($isFormLockedForEdit)
                                                <span class="inline-flex h-9 items-center rounded-md bg-slate-100 px-3 text-xs font-semibold text-slate-500">Locked</span>
                                            @else
                                                <div class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1">
                                                    <form id="save-line-{{ $schedule->id }}" method="POST" action="{{ route('schedules.update', $schedule) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="notes" value="{{ $schedule->notes }}">
                                                        <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['save'] }}" onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                                                <path d="M17 21v-8H7v8"/>
                                                                <path d="M7 3v5h8"/>
                                                            </svg>
                                                            <span>Save</span>
                                                        </button>
                                                    </form>

                                                    @if (! $isApprovalView && $canManage)
                                                        <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule line?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="h-9 w-24 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['delete'] }}" onmouseover="this.style.background='#cbd5e1'" onmouseout="this.style.background='#e2e8f0'">Delete</button>
                                                        </form>
                                                    @endif

                                                    @if ($isApprovalView && $canApprove && $schedule->status === 'approved')
                                                        <span class="inline-flex h-9 w-24 items-center justify-center rounded-md text-xs font-semibold" style="{{ $buttonStyles['approved_badge'] }}">Approved</span>
                                                        <form method="POST" action="{{ route('schedules.reject', $schedule) }}" class="flex flex-nowrap items-center gap-2">
                                                            @csrf
                                                            <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['reject'] }}" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <path d="M18 6 6 18M6 6l12 12"/>
                                                                </svg>
                                                                <span>Reject</span>
                                                            </button>
                                                            <input type="text" name="reason" placeholder="Reason(optional)" class="h-9 w-36 rounded-md border-slate-300 text-xs">
                                                        </form>
                                                    @elseif ($isApprovalView && $canApprove && $schedule->status === 'submitted')
                                                        <form method="POST" action="{{ route('schedules.approve', $schedule) }}">
                                                            @csrf
                                                            <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['approve'] }}" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <path d="M20 6 9 17l-5-5"/>
                                                                </svg>
                                                                <span>Approve</span>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('schedules.reject', $schedule) }}" class="flex flex-nowrap items-center gap-2">
                                                            @csrf
                                                            <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['reject'] }}" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <path d="M18 6 6 18M6 6l12 12"/>
                                                                </svg>
                                                                <span>Reject</span>
                                                            </button>
                                                            <input type="text" name="reason" placeholder="Reason (optional)" class="h-9 w-36 rounded-md border-slate-300 text-xs">
                                                        </form>
                                                    @elseif ($isApprovalView && $canApprove && $schedule->status === 'rejected')
                                                        <span class="inline-flex h-9 w-24 items-center justify-center rounded-md text-xs font-semibold" style="{{ $buttonStyles['rejected_badge'] }}">Rejected</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @else
                                        <td class="border border-slate-200 px-3 py-3 text-slate-700">{{ $schedule->starts_at->format('H:i') }}</td>
                                        <td class="border border-slate-200 px-3 py-3 text-slate-700">{{ $schedule->ends_at->format('H:i') }}</td>
                                        <td class="border border-slate-200 px-3 py-3 text-slate-700">{{ $schedule->notes ?: '-' }}</td>
                                        <td class="border border-slate-200 px-3 py-3">
                                            @if ($isApprovalView && $canApprove && $schedule->status === 'approved')
                                                <div class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1">
                                                    <span class="inline-flex h-9 w-24 items-center justify-center rounded-md text-xs font-semibold" style="{{ $buttonStyles['approved_badge'] }}">Approved</span>
                                                    <form method="POST" action="{{ route('schedules.reject', $schedule) }}" class="flex flex-nowrap items-center gap-2">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['reject'] }}" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M18 6 6 18M6 6l12 12"/>
                                                            </svg>
                                                            <span>Reject</span>
                                                        </button>
                                                        <input type="text" name="reason" placeholder="Reason(optional)" class="h-9 w-36 rounded-md border-slate-300 text-xs">
                                                    </form>
                                                </div>
                                            @elseif ($isApprovalView && $canApprove && $schedule->status === 'submitted')
                                                <div class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1">
                                                    <form method="POST" action="{{ route('schedules.approve', $schedule) }}">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['approve'] }}" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M20 6 9 17l-5-5"/>
                                                            </svg>
                                                            <span>Approve</span>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('schedules.reject', $schedule) }}" class="flex flex-nowrap items-center gap-2">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-md text-xs font-semibold transition" style="{{ $buttonStyles['reject'] }}" onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M18 6 6 18M6 6l12 12"/>
                                                            </svg>
                                                            <span>Reject</span>
                                                        </button>
                                                        <input type="text" name="reason" placeholder="Reason (optional)" class="h-9 w-36 rounded-md border-slate-300 text-xs">
                                                    </form>
                                                </div>
                                            @elseif ($isApprovalView && $canApprove && $schedule->status === 'rejected')
                                                <span class="inline-flex h-9 w-24 items-center justify-center rounded-md text-xs font-semibold" style="{{ $buttonStyles['rejected_badge'] }}">Rejected</span>
                                            @else
                                                <span class="text-xs text-slate-500">Read-only</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (! $isApprovalView && $canSubmitReapproval)
                    <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex justify-end">
                            <form method="POST" action="{{ route('schedules.form.submit-reapproval') }}">
                                @csrf
                                <input type="hidden" name="form_id" value="{{ $form->id }}">
                                <button type="submit" class="h-9 rounded-md px-6 text-sm font-semibold transition" style="background:#0284c7;color:#ffffff;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">Submit for Re-Approval</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
