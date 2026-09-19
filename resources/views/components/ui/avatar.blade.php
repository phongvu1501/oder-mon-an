@props(['user', 'size' => 'md'])
<span class="user-avatar user-avatar--{{ $size }}">
    @if ($user->avatar)
        <img src="{{ Storage::url($user->avatar) }}" alt="Ảnh đại diện của {{ $user->name }}">
    @else
        <span>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    @endif
</span>
