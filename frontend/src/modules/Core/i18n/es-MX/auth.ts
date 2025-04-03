export default {
  forms: {
    common: {
      email: 'Correo electrónico',
      name: 'Nombre',
      password: 'Contraseña',
      passwordConfirm: 'Confirmar contraseña',
    },
    login: {
      title: 'Iniciar sesión',
      button: 'Entrar',
      loggedInAs: 'Conectado como {name}',
      goTo: 'Ir a',
      welcomePage: 'Página de bienvenida',
      link: '¿Ya tienes una cuenta?',
    },
    logout: {
      button: 'Cerrar sesión',
    },
    oauth: {
      apple: 'Apple',
      google: 'Google',
      link: '¿Prefieres correo/contraseña?',
      logInWith: 'Iniciar sesión con {provider}',
      title: 'Iniciar sesión con proveedor',
    },
    signup: {
      button: 'Registrarse',
      link: '¿Necesitas una cuenta?',
      title: 'Crear cuenta',
    },
  },
  status: {
    errors: {
      activeFlowExists: 'Ya hay un flujo activo.',
      emailAlreadyInUse: 'Este correo ya está en uso.',
      emailTaken: 'Este correo ya está en uso.',
      invalidConfig: 'Configuración inválida.',
      invalidCredentials: 'Correo o contraseña inválidos.',
      invalidNonce: 'Nonce inválido.',
      invalidProvider: 'Proveedor inválido.',
      invalidToken: 'Token inválido.',
      invalidUser: 'Usuario inválido.',
      login: 'No se pudo iniciar sesión, intenta más tarde.',
      mismatch: 'Las contraseñas no coinciden.',
      providerIdTaken: 'Este ID de proveedor ya está en uso.',
      userNotFound: 'Usuario no encontrado.',
    },
    warnings: {
      emailNotVerified: 'Esta cuenta no ha sido verificada.',
    },
    success: {
      clientTokenGranted: 'Autenticación exitosa, intercambia el nonce por sesión',
      loggedIn: 'Sesión iniciada correctamente.',
      loggedOut: 'Sesión cerrada correctamente.',
      loginOk: 'Inicio de sesión exitoso.',
      registered: 'Registro completado.',
      userCreated: 'Usuario creado exitosamente.',
    },
  },
};
