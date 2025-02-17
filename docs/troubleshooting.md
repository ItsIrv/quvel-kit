# Troubleshooting

## Common Issues & Fixes

### **SSL Warnings**

If you encounter browser warnings about SSL certificates:

- Ensure `mkcert` is installed correctly:

  ```bash
  mkcert -install
  ```

- Check if the SSL certs exist:

  ```bash
  ls docker/certs/
  ```

- Restart Docker to apply changes:

  ```bash
  ./scripts/restart.sh
  ```

### **Containers Not Starting**

If Docker services fail to start:

```bash
./scripts/start.sh
```

### **Database Issues**

- If MySQL isn't responding:

  ```bash
  docker exec -it quvel-mysql mysql -u root -p
  ```

- If migrations fail, ensure Laravel is booted:

  ```bash
  docker exec -it quvel-app php artisan migrate --force
  ```

### **Reset Everything**

If issues persist, try a full reset:

```bash
./scripts/reset.sh
```

### **Clear Docker Cache**

If issues persist, try clearing Docker cache:

```bash
docker system prune -a --volumes
```

For further debugging, check logs:

```bash
./scripts/logs.sh
```
