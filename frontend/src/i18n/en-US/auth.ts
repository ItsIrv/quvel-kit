export default {
  forms: {
    common: {
      email: 'Email',
      password: 'Password',
    },
    login: {
      title: 'Login to QuVel Kit',
      button: 'Login',
      loggedInAs: 'Logged in as {name}',
      logout: 'Logout',
      goTo: 'Go to',
      welcomePage: 'Welcome Page',
    },
  },
  validation: {
    passwordMismatch: 'Passwords must match.',
  },
  errors: {
    invalidCredentials: 'Invalid email or password.',
    emailAlreadyInUse: 'This email is already in use.',
    userNotFound: 'User not found.',
  },
  warnings: {
    emailNotVerified: 'This account has not been verified.',
  },
  success: {
    loggedOut: 'You have been logged out.',
    loggedIn: 'You have been logged in.',
  },
};
