<?php

return [
    'errors' => [
        'userNotFound' => 'User not found.',
        'invalidCredentials' => 'Invalid email or password.',
        'emailAlreadyInUse' => 'This email is already in use.',
        'invalidProvider' => 'Invalid :provider.',
        'invalidNonce' => 'Invalid nonce.',
        'invalidToken' => 'Invalid token.',
        'invalidUser' => 'Invalid user.',
        'emailTaken' => 'This email is already in use.',
        'providerIdTaken' => 'This :provider ID is already in use.',
        'invalidConfig' => 'Invalid configuration.',
        'activeFlowExists' => 'An active flow already exists.',
        'internalError' => 'Internal error.',
    ],
    'warnings' => [
        'emailNotVerified' => 'This account has not been verified.',
    ],
    'success' => [
        'loggedOut' => 'You have been logged out.',
        'loggedIn' => 'You have been logged in.',
        'registered' => 'You have been registered.',
        'userCreated' => 'User created successfully.',
        'loginOk' => 'Login successful.',
        'clientTokenGranted' => 'Authentication successful, exchange nonce for session',
    ],
];
