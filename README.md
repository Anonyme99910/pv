#  PV System Monitoring Platform

An advanced AI-powered monitoring and fault detection system for photovoltaic (PV) solar panel installations.

## Features

- **Real-time Dashboard** - Live monitoring of 248+ solar panels with power output visualization
- **AI Fault Detection** - Machine learning-powered anomaly detection with 94%+ confidence
- **Predictive Maintenance** - Smart scheduling and task management for technicians
- **Advanced Analytics** - Performance trends, efficiency analysis, and RUL predictions
- **Multi-language Support** - English and French localization
- **Role-based Access** - Admin, Technician, and Viewer roles
- **Dark Mode** - Beautiful dark/light theme switching
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile

##  Tech Stack

- **Frontend:** Vue 3 (Composition API)
- **Routing:** Vue Router 4
- **State Management:** Pinia
- **Styling:** TailwindCSS
- **Charts:** Chart.js
- **Icons:** Lucide Icons
- **Build Tool:** Vite
- **Internationalization:** Vue I18n

## Installation

```bash
# Install dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

##  Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@soma.com | admin123 |
| Technician | tech@soma.com | tech123 |
| Viewer | viewer@soma.com | viewer123 |

##  Pages

- **Dashboard** - Real-time system overview with KPIs and live charts
- **Fault Detection** - AI-powered fault analysis with detailed diagnostics
- **Maintenance** - Task scheduling and calendar view
- **Analytics** - Performance metrics and predictive insights
- **Settings** - User management and system configuration
- **Profile** - User profile and activity history

##  Features Highlights

### Real-time Monitoring
- Live power output tracking
- 248 panel grid with status indicators
- Voltage, current, and temperature trends
- Instant alerts and notifications

### AI-Powered Fault Detection
- Automatic anomaly detection
- Root cause analysis
- Confidence scoring
- Suggested corrective actions

### Predictive Maintenance
- Smart task scheduling
- Technician assignment
- Calendar integration
- Task status tracking

### Advanced Analytics
- Efficiency trend analysis
- Dust accumulation tracking
- Temperature vs output correlation
- Remaining useful life (RUL) predictions

##  Internationalization

The platform supports multiple languages:
- 🇬🇧 English
- 🇫🇷 French

##  Project Structure

```
pv-system/
├── src/
│   ├── components/     # Reusable Vue components
│   ├── views/          # Page components
│   ├── layouts/        # Layout components
│   ├── stores/         # Pinia state stores
│   ├── router/         # Vue Router configuration
│   ├── i18n/           # Internationalization
│   ├── services/       # API services
│   └── utils/          # Utility functions
├── public/             # Static assets
└── package.json        # Dependencies
```

##  Configuration

The system uses mock API data for demonstration. To connect to a real backend:

1. Update `src/services/api.js` with your API endpoints
2. Configure authentication in `src/stores/auth.js`
3. Set environment variables in `.env` file

##  Performance

-  Fast initial load with Vite
-  Smooth animations and transitions
-  Fully responsive design
-  Accessible UI components
