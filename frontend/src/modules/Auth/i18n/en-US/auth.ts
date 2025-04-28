export default {
  forms: {
    common: {
      email: 'Email',
      name: 'Name',
      password: 'Password',
      passwordConfirm: 'Confirm Password',
    },
    login: {
      title: 'Login',
      button: 'Login',
      loggedInAs: 'Logged in as {name}',
      goTo: 'Go to',
      welcomePage: 'Welcome Page',
      link: 'Have an account?',
    },
    logout: {
      button: 'Logout',
    },
    oauth: {
      apple: 'Apple',
      google: 'Google',
      link: 'Use Email/Password instead?',
      logInWith: '{provider} Login',
      title: 'Login with Provider',
    },
    signup: {
      button: 'Sign Up',
      link: 'Need an account?',
      title: 'Sign Up',
    },
  },
  status: {
    errors: {
      activeFlowExists: 'An active flow already exists.',
      emailAlreadyInUse: 'This email is already in use.',
      emailTaken: 'This email is already in use.',
      invalidConfig: 'Invalid configuration.',
      invalidCredentials: 'Invalid email or password.',
      invalidNonce: 'Invalid nonce.',
      invalidProvider: 'Invalid provider.',
      invalidToken: 'Invalid token.',
      invalidUser: 'Invalid user.',
      login: 'Failed to log in, please try again later',
      mismatch: 'Password does not match.',
      providerIdTaken: 'This provider ID is already in use.',
      userNotFound: 'User not found.',
    },
    warnings: {
      emailNotVerified: 'This account has not been verified.',
    },
    success: {
      clientTokenGranted: 'Authentication successful, exchange nonce for session',
      loggedIn: 'You have been logged in.',
      loggedOut: 'You have been logged out.',
      signedUp: 'You have been signed up.',
      userCreated: 'User created successfully.',
      checkYourEmail: 'Check your email for a verification link.',
    },
  },
};
