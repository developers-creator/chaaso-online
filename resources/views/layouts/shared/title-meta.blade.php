<meta charset="utf-8" />
<title>{{ $title ?? '' }} | Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="app-url" content="{{ url('/') }}">
<meta name="file-base-url" content="{{ url('/storage/') }}">
@if (($favicon = SettingHelper::get_field('site_favicon')) && ($siteFavicon = MediaHelper::getImageById($favicon)))
<link rel="shortcut icon" href="{{ MediaHelper::WsrvService($siteFavicon) }}" type="{{ $siteFavicon->type }}">
@endif
