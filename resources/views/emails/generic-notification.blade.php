<x-mail::message>
# {{ $title }}

{{ $body }}

<x-mail::button :url="$url">
Open in ERP
</x-mail::button>

Thanks,<br>
{{ $companyName }}
</x-mail::message>
