# Use official Node.js image
FROM node:22-alpine

# Set working directory
WORKDIR /frontend

# Install Quasar CLI globally
RUN yarn global add @quasar/cli

# Expose Quasar dev server port
EXPOSE 9000

# Default command to start Quasar
CMD ["yarn", "dev"]
