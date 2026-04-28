# WATI WhatsApp Integration — Frontend

React SPA frontend for the WATI WhatsApp Integration backend, built with Create React App.

## Tech Stack

- **React 18** with TypeScript (Create React App)
- **Tailwind CSS** for styling
- **React Router** for client-side routing
- **Axios** for API calls
- **React Hot Toast** for notifications

## Setup

### 1. Install Dependencies

```bash
cd frontend
npm install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and set the backend API URL:

```env
REACT_APP_API_URL=http://localhost:8000
```

### 3. Start Development Server

```bash
npm start
```

The frontend runs at `http://localhost:3000` and proxies API requests to the Laravel backend at `http://localhost:8000`.

### 4. Build for Production

```bash
npm run build
```

Output goes to `frontend/build/`.

## Pages

| Route | Page | Description |
|-------|------|-------------|
| `/login` | Login | Email/password authentication |
| `/` | Dashboard | Message statistics overview |
| `/messages` | Messages | Paginated message list with filters |
| `/send` | Send Message | Compose text, template, media, or button messages |
| `/conversations` | Conversations | Phone-based conversation list |
| `/conversations/:phone` | Conversation Detail | Chat-style message thread |

## Features

- **Authentication**: Sanctum token-based login/logout with persistent session
- **Dashboard**: Real-time stats for total, incoming, outgoing, delivered, failed, and pending messages
- **Message Management**: Full filter set (status, direction, phone, date range) with pagination
- **Send Messages**: Support for all 4 message types — text, template, media, interactive buttons
- **Conversations**: WhatsApp-style chat bubbles grouped by phone number
- **Resend Failed**: One-click resend for failed messages
- **Responsive**: Tailwind-based responsive layout with sidebar navigation
