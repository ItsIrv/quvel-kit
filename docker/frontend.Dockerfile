# Use official Node.js image
FROM node:22-alpine

# Set working directory
WORKDIR /frontend

# Install Quasar CLI globally
RUN yarn global add @quasar/cli

# Default command to start Quasar
CMD ["yarn", "dev"]
