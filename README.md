# 🃏 Card Battle API
> Aplicación full-stack para gestionar y jugar online un juego de cartas por turnos: registro de usuarios, armado de mazos y partidas contra un servidor con lógica de ventajas por atributo (tipo "piedra, papel o tijera" con estilo Pokémon).

Proyecto grupal para la materia **Seminario de Lenguajes: PHP, React y API REST** — Ingeniería en Computación, UNLP.

## 🛠️ Tecnologías
- **Backend / API:** PHP, Slim Framework 4, JWT (firebase/php-jwt), MySQL 8, Docker / Docker Compose, phpMyAdmin
- **Frontend:** React 19, Vite, React Router DOM, Axios, Bootstrap 5

## 🚀 Funcionalidades
- Registro y login de usuarios con autenticación por token JWT
- Alta, edición, listado y baja de mazos (hasta 3 por usuario, 5 cartas por mazo)
- Partidas contra un servidor con IA simple: elige cartas válidas de su propio mazo sin repetir
- Sistema de ventajas por atributo (ej. fuego vence a planta, +30% de ataque) que define el ganador de cada ronda
- Historial de jugadas por partida y cierre automático al llegar a la quinta ronda
- Panel de estadísticas públicas con ranking de usuarios ordenable (victorias, empates, derrotas, winrate)
- Edición de perfil de usuario logueado

## 📂 Estructura
```
backend/    → API REST en Slim (PHP) + configuración Docker + MySQL
  ├── slim/           → Controllers, Models, Middleware y rutas (index.php)
  ├── docker/         → Dockerfile del servicio de PHP/Slim
  └── db/             → Inicialización de la base de datos (montada en el contenedor MySQL)
frontend/   → Cliente React (Vite): páginas de login, registro, mazos, juego y estadísticas
```

## 📖 Sobre el proyecto
Este trabajo se desarrolló en dos entregas: primero la API REST (backend en PHP/Slim con MySQL) siguiendo un contrato de endpoints definido por la cátedra, y luego un cliente en React consumiendo esa misma API. La lógica central del juego —determinar la jugada del servidor, resolver ventajas por atributo y definir el ganador de cada partida— está implementada íntegramente en el backend; el frontend se limita a consumir los endpoints y mostrar el estado de la partida.

## 🔧 Cómo correrlo

### Backend (API)
Requiere Docker.
```bash
cd backend
cp .env.dist .env
docker volume create seminariophp
docker compose up -d
```
La API queda disponible en `http://localhost` (puerto configurable en `.env`) y phpMyAdmin en `http://localhost:8080`.

### Frontend
Requiere Node.js.
```bash
cd frontend
npm install
npm run dev
```
Por defecto apunta a `http://localhost:80` (configurable en `frontend/.env`, variable `VITE_API_BASE_URL`).
