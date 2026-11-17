<x-mail::message>
# New approval request

Hello {{ $approver->name }},

A new approval request is waiting in your inbox.

**Request:** {{ class_basename($approvalRequest->approvable_type) }} #{{ $approvalRequest->approvable_id }}
**Submitted at:** {{ $approvalRequest->submitted_at?->format('Y-m-d H:i') }}

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/my-approvals'">
Review request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
