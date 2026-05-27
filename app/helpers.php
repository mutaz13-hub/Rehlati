<?php

    
function maskIp(string $ip): string
{
    $parts = explode('.', $ip);

    $parts[3] = '***';

    $maskedIP = implode('.', $parts);

    return $maskedIP;
}
  
  function maskEmail(string $email): string
  {
    $parts = explode('@', $email);

    $user_name = substr($parts[0], 0, 1) . str_repeat('*', max(strlen($parts[0]) - 1, 3));

    return $user_name . '@' . $parts[1];
  }


