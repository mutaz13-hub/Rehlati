<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
{{ __('Hello :attribute', ['attribute' => $name]) }} <br>
{{ __('You requested a password reset. Please enter the code below to reset your password: ') }} <br>
{{ $code }} <br>
{{ __('The code expires in 30 minutes') }} <br>
{{ __('If you did not request this, ignore this email.') }}
</body>
</html>
