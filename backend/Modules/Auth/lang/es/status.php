<?php

return [
    'errors'   => [
        'userNotFound'       => 'Usuario no encontrado.',
        'invalidCredentials' => 'Correo electrónico o contraseña inválidos.',
        'emailAlreadyInUse'  => 'Este correo electrónico ya está en uso.',
        'invalidProvider'    => 'Proveedor inválido: :provider.',
        'invalidNonce'       => 'Nonce inválido.',
        'invalidToken'       => 'Token inválido.',
        'invalidUser'        => 'Usuario inválido.',
        'emailTaken'         => 'Este correo electrónico ya está en uso.',
        'providerIdTaken'    => 'Este ID de :provider ya está en uso.',
        'invalidConfig'      => 'Configuración inválida.',
        'activeFlowExists'   => 'Ya existe un flujo activo.',
        'internalError'      => 'Error interno.',
    ],
    'warnings' => [
        'emailNotVerified' => 'Esta cuenta no ha sido verificada.',
    ],
    'success'  => [
        'loggedOut'          => 'Has cerrado sesión.',
        'loggedIn'           => 'Has iniciado sesión.',
        'registered'         => 'Te has registrado correctamente.',
        'userCreated'        => 'Usuario creado exitosamente.',
        'loginOk'            => 'Inicio de sesión exitoso.',
        'clientTokenGranted' => 'Autenticación exitosa, intercambia el nonce por una sesión.',
    ],
];
