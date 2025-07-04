export default {
  title: 'Dashboard',
  welcome: 'Welcome back, {name}!',
  subtitle: "Here's what's happening with your projects today.",
  navigation: 'Navigation',
  breadcrumbs: {
    home: 'Dashboard',
  },
  nav: {
    overview: 'Overview',
    projects: 'Projects',
    tasks: 'Tasks',
    calendar: 'Calendar',
    reports: 'Reports',
    analytics: 'Analytics',
    settings: 'Settings',
    sections: {
      workspace: 'Workspace',
      analytics: 'Analytics',
      account: 'Account',
    },
  },
  stats: {
    projects: 'Active Projects',
    tasks: 'Total Tasks',
    team: 'Team Members',
    revenue: 'Revenue',
  },
  quickActions: {
    title: 'Quick Actions',
    newProject: 'New Project',
    invite: 'Invite Member',
    report: 'Generate Report',
    upload: 'Upload Files',
  },
  activity: {
    title: 'Recent Activity',
    projectCreated: 'New project created',
    taskCompleted: 'Task completed',
    memberJoined: 'New member joined',
  },
  getStarted: {
    title: 'Get Started',
  },
  guides: {
    setup: {
      title: 'Complete Setup',
      description: 'Finish setting up your workspace with integrations and preferences.',
    },
    tutorial: {
      title: 'Watch Tutorial',
      description: 'Learn how to make the most of your dashboard with our video guides.',
    },
    api: {
      title: 'API Documentation',
      description: 'Integrate with our API to automate workflows and extend functionality.',
    },
  },
  actions: {
    create: 'Create New',
    learnMore: 'Learn More',
  },
  settings: {
    title: 'Settings',
    twoFactor: {
      title: 'Two-Factor Authentication',
      description: 'Add an extra layer of security to your account by enabling two-factor authentication.',
      notEnabled: 'Two-factor authentication is not currently enabled for your account.',
      enabled: 'Two-factor authentication is enabled for your account.',
      enable: 'Enable Two-Factor Authentication',
      disable: 'Disable',
      recoveryCodes: 'Recovery codes can be used to access your account if you lose access to your authentication device.',
      viewRecoveryCodes: 'View Recovery Codes',
      regenerateCodes: 'Regenerate Codes',
      setup: {
        scanQR: 'Scan QR Code',
        scanInstructions: 'Scan this QR code with your authenticator app (like Google Authenticator or Authy).',
        cantScan: "Can't scan the code?",
        manualEntry: 'Enter this key manually in your authenticator app:',
        verify: 'Verify Setup',
        verifyInstructions: 'Enter the 6-digit code from your authenticator app to verify the setup.',
        verificationCode: 'Verification Code',
        verifyButton: 'Verify and Enable',
        recoveryCodes: 'Save Recovery Codes',
        recoveryWarning: 'Save these recovery codes in a secure place. Each code can only be used once to access your account if you lose your authentication device.',
        downloadCodes: 'Download Recovery Codes',
      },
      disableDialog: {
        title: 'Disable Two-Factor Authentication',
        message: 'Are you sure you want to disable two-factor authentication? This will make your account less secure.',
        confirm: 'Disable',
      },
      recoveryDialog: {
        title: 'Recovery Codes',
      },
      confirmPassword: {
        title: 'Confirm Password',
        message: 'Please confirm your password to enable two-factor authentication.',
      },
      errors: {
        enableFailed: 'Failed to enable two-factor authentication',
        invalidCode: 'Invalid verification code. Please try again.',
        disableFailed: 'Failed to disable two-factor authentication',
        codesFailed: 'Failed to retrieve recovery codes',
        regenerateFailed: 'Failed to regenerate recovery codes',
      },
      success: {
        enabled: 'Two-factor authentication has been enabled',
        disabled: 'Two-factor authentication has been disabled',
        codesRegenerated: 'Recovery codes have been regenerated',
      },
    },
  },
};