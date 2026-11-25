{{-- One-step way out of a kind of email. $user: the recipient; $category: a NotificationCategory value. --}}
<p style="margin-top: 8px; font-size: 12px;">
    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('email-unsubscribe.show', ['user' => $user->id, 'category' => $category]) }}" style="color: inherit;">Stop emails like this</a>
</p>
