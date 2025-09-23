# SGC E-Learning Platform

## Overview

SGC E-Learning Platform is a corporate e-learning system built with PHP 8.2+ featuring a distinctive claymorphism design theme. The platform provides a multi-role learning environment supporting administrators, instructors, and students with a sophisticated glass-morphism visual style using a sky blue and mint green color palette.

The system is designed with a modular architecture emphasizing reusable components, centralized theming, and role-based access control. The platform uses SQLite as its primary database with support for MySQL and PostgreSQL configurations.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
The platform implements a **centralized theme system** with claymorphism design patterns. The theme architecture is structured as follows:

- **Configuration-driven design**: JSON-based theme configuration files for colors, typography, spacing, components, and layouts
- **CSS variable system**: Centralized CSS custom properties for consistent styling across components
- **Modular CSS components**: Separate stylesheets for buttons, forms, cards, and other UI elements
- **Responsive design**: Mobile-first approach with configurable breakpoints
- **Animation system**: Predefined transitions, hover effects, and entrance animations
- **Icon system**: SVG-based icon library with corporate styling

The theme system supports multiple layouts (admin, student, instructor, public, auth) with glassmorphism effects including backdrop blur, translucent backgrounds, and gradient overlays.

### Backend Architecture
The platform follows a **modular MVC pattern** with the following core components:

- **PSR-4 autoloader**: Organized namespace structure for automatic class loading
- **Configuration management**: JSON-based configuration files for application, database, and theme settings
- **Modular routing system**: Route-based request handling with controller dispatch
- **Database abstraction layer**: Support for SQLite (primary), MySQL, and PostgreSQL
- **Session management**: Secure session handling with CSRF protection
- **Role-based permissions**: Multi-tier user authentication (admin, instructor, student)

The development approach emphasizes **"general structure first, then details"** - building the overall architecture before implementing specific features.

### Data Storage Solutions
- **Primary database**: SQLite embedded database for development and small deployments
- **Alternative support**: MySQL and PostgreSQL configuration options
- **Schema design**: Foreign key relationships with referential integrity
- **Data persistence**: File-based SQLite storage in `database/elearning.db`

### Authentication and Authorization
- **Multi-role system**: Admin, instructor, and student role hierarchy
- **Session-based authentication**: Secure session management with configurable lifetime
- **CSRF protection**: Built-in cross-site request forgery protection
- **Password policies**: Configurable minimum password requirements
- **Session security**: HTTP-only cookies with optional secure flag

## External Dependencies

### Core Dependencies
- **PHP 8.2+**: Required runtime with PDO, JSON, and Session extensions
- **SQLite**: Primary database engine (embedded)
- **Optional databases**: MySQL 5.7+/8.0+ or PostgreSQL 12+

### Frontend Dependencies
- **Modern browsers**: Support for CSS custom properties, backdrop-filter, and CSS Grid
- **No external CSS frameworks**: Custom claymorphism theme system
- **SVG icons**: Inline SVG icon system (no external icon libraries)

### Development Dependencies
- **Built-in PHP server**: Development server capability
- **File system**: Local file storage for database and uploads
- **No external build tools**: Pure CSS and vanilla JavaScript approach

### Potential Integrations
The architecture supports future integration with:
- External authentication providers (OAuth, LDAP)
- Content delivery networks for media assets
- Email services for notifications
- Analytics and reporting services
- Learning management system standards (SCORM, xAPI)