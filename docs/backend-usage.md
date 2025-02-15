# Backend Usage

## 🛠️ Accessing the Laravel Backend

The backend of QuVel Kit is powered by **Laravel** and runs in a Docker container. Below are the steps to interact with the backend for development, migrations, and debugging.

---

## 🔧 Running Migrations & Database Commands

### **1️⃣ Open a Terminal in the Laravel Container**

To run artisan commands inside the backend container:

```bash
./scripts/start.sh   # Ensure services are running

docker exec -it quvel-app sh   # Access the Laravel container
```

Once inside the container, you can run commands as you normally would:

```bash
php artisan migrate --force  # Run database migrations
php artisan db:seed  # Seed the database
```

Exit the container with:

```bash
exit
```

---

## 🔄 Resetting the Database

If you need to reset the database, run:

```bash
./scripts/reset.sh
```

This will stop all containers, remove volumes, and restart everything fresh.

---

## 📂 Storage & Linking

If you encounter issues with file uploads or missing storage links, run:

```bash
docker exec -it quvel-app php artisan storage:link
```

---

## 🔍 Debugging Backend Issues

### View Logs

```bash
./scripts/logs.sh
```

### Open Tinker (Laravel REPL)

```bash
docker exec -it quvel-app php artisan tinker
```

### Restart Laravel Service

```bash
./scripts/restart.sh
```

---

## 🏗️ Future Improvements

- Automate migrations for fresh setups with prompts.
- Add better error handling for database initialization.

🚀 **Now you're set up to develop with the Laravel backend!**
