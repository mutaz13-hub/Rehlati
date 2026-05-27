<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    

{{ __('Hello :attribute', ['attribute' => $name]) }} <br>

{{ __('Welcome To Our App!') }} <br>

{{ __('This Is Your Verification Code: :code', ['code' => $code ]) }} <br>

{{ __('Enjoy The Experience!') }}

</body>
</html>
