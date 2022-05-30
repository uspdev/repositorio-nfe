@extends('layouts.app')

@section('content')
  <div>
    Emails: {{ count($messages) }}
  </div>

  @foreach ($messages as $message)
    @php
      // \App\Models\Email::storeNewMessage($message);
      // Log::notice('chegou nova mensagem');
    @endphp
    <div>
      {{ json_encode($message->getFlags()) }} -
      {{ $message->getFolder()->name }} -
      {{ $message->date }}<br>
      {{ $message->from }} | {{ $message->to }} | {{ $message->subject }}
      | Anexos: {{ $message->getAttachments()->count() }}
      @php
        if (!$message->hasAttachments()) {
            $message->move('PARSED');
        }
      @endphp
      @foreach ($message->getAttachments() as $attachment)
        @if ($attachment->getAttributes()['content_type'] == 'text/xml')
          @php $message->setFlag('xml'); @endphp
          (flag:xml)
        @endif
        <div class="ml-3">
          {{ $attachment->getAttributes()['content_type'] }} -
          {{ $attachment->getAttributes()['name'] }} -
          {{ (int) ($attachment->getAttributes()['size'] / 1024) }}KB
        </div>
      @endforeach
    </div>
    <br>
  @endforeach
@endsection
