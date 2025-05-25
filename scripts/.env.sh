#!/bin/bash

echo "⚙️  Ensuring .env files exist..."

# Backend .env
if [ ! -f backend/.env ]; then
  echo "📄 Creating Laravel .env file..."
  cp backend/.env.example backend/.env
fi

# Frontend .env
if [ ! -f frontend/.env ]; then
  echo "📄 Creating Quasar .env file..."
  cp frontend/.env.example frontend/.env
fi
