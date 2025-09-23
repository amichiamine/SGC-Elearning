# SGC E-Learning Platform

## Description du Projet

Plateforme d'e-learning modulaire destinée aux écoles privées, académies de formations en ligne, et formateurs indépendants. Architecture entièrement modulaire avec vues indépendantes et système de gestion avancé des rôles et accès.

## Technologies Utilisées

- **Backend :** PHP 8.2+ avec architecture MVC
- **Frontend :** HTML5, CSS3, JavaScript ES6+ (Vanilla)
- **Base de données :** SQLite (par défaut) migrable vers MySQL et PostgreSQL
- **Format de données :** JSON pour configuration et échanges
- **Autoloader :** PSR-4 compatible

## Politiques de Développement

### 1. Structure Modulaire Complète
- Chaque module est indépendant et auto-suffisant
- Possibilité d'extraire et réutiliser n'importe quel module dans un autre projet
- Séparation claire entre logique métier, présentation et données

### 2. Développement par Vue
- Chaque vue est totalement indépendante (HTML, CSS, JS, PHP)
- Portabilité maximale de chaque vue
- Intégration possible dans d'autres projets sans modification

### 3. Zéro Chemins Absolus
- Utilisation exclusive de `__DIR__` et chemins relatifs
- Compatibilité universelle : XAMPP, serveurs web mutualisés, cloud
- Base de données embarquée SQLite pour portabilité maximale

### 4. Interface Corporate
- Zéro emojis - uniquement des icônes corporate
- Design professionnel et moderne
- Interface adaptée aux environnements d'entreprise

### 5. Thème Claymorphism Centralisé **[NOUVELLE POLITIQUE]**
- Design claymorphism stylé et moderne dans dossier **Theme/** indépendant et structuré
- **Changement complet de thème** en manipulant uniquement le dossier Theme
- **Configuration centralisée** de tous les styles et paramètres dans Theme/
- **Système modulaire** permettant modification simple et rapide de tout le thème
- **Contient le style et paramètres** de tous les composants utilisés par toutes les vues
- **Facilite le changement total du thème** de toute l'application via le dossier Theme uniquement

### 6. Développement Modulaire Séquentiel
- Construction de chaque vue de manière générale puis implémentation des détails
- Approche modulaire avec vue principale d'abord
- Configuration de base puis enchaînement modulaire des vues
- Respect de la modularité du projet à chaque étape

## Structure Modulaire en Arbre

```
elearning-platform/
│
├── index.php                          # Point d'entrée principal
├── SGC-Elearning.md                   # Documentation projet
├── .htaccess                          # Configuration serveur web
│
├── config/                            # Configuration générale
│   ├── app.json                       # Config application
│   ├── database.json                  # Config base de données
│   ├── roles.json                     # Définition rôles et permissions
│   └── routes.json                    # Configuration des routes
│
├── core/                              # Cœur du système
│   ├── Autoloader.php                 # Chargeur automatique PSR-4
│   ├── Application.php                # Classe principale
│   ├── Config.php                     # Gestionnaire configuration
│   ├── Database.php                   # Abstraction base de données
│   ├── Router.php                     # Système de routage
│   ├── Auth.php                       # Authentification
│   ├── RoleManager.php                # Gestion rôles et permissions
│   ├── View.php                       # Classe de base pour vues
│   ├── Controller.php                 # Classe de base contrôleurs
│   ├── Model.php                      # Classe de base modèles
│   ├── Session.php                    # Gestion sessions
│   ├── Security.php                   # Sécurité et validation
│   ├── AuditLogger.php                # Logs d'audit
│   └── Utils.php                      # Utilitaires généraux
│
├── theme/                             # SYSTÈME DE THÈME CENTRALISÉ [DOSSIER INDÉPENDANT]
│   ├── config/                        # Configuration complète du thème
│   │   ├── theme.json                 # Paramètres principaux du thème
│   │   ├── colors.json                # Palette de couleurs complète
│   │   ├── typography.json            # Configuration typographique
│   │   ├── spacing.json               # Espacements et dimensions
│   │   ├── components.json            # Paramètres de tous les composants
│   │   ├── layouts.json               # Configuration des layouts
│   │   └── animations.json            # Paramètres des animations claymorphism
│   │
│   ├── css/                           # Tous les styles du thème
│   │   ├── variables.css              # Variables CSS globales
│   │   ├── reset.css                  # Reset CSS
│   │   ├── claymorphism.css           # Style claymorphism principal
│   │   ├── typography.css             # Styles typographiques
│   │   ├── animations.css             # Animations et transitions
│   │   ├── components/                # Styles de tous les composants
│   │   │   ├── buttons.css            # Tous les boutons
│   │   │   ├── forms.css              # Tous les formulaires
│   │   │   ├── cards.css              # Toutes les cartes
│   │   │   ├── navigation.css         # Toute la navigation
│   │   │   ├── modals.css             # Toutes les modales
│   │   │   ├── tables.css             # Tous les tableaux
│   │   │   ├── alerts.css             # Toutes les alertes
│   │   │   ├── badges.css             # Tous les badges
│   │   │   ├── carousel.css           # Carrousels
│   │   │   └── banners.css            # Bannières
│   │   ├── layouts/                   # Layouts de toutes les vues
│   │   │   ├── base.css               # Layout de base
│   │   │   ├── admin.css              # Layout admin
│   │   │   ├── student.css            # Layout étudiant
│   │   │   ├── instructor.css         # Layout formateur
│   │   │   ├── public.css             # Layout public
│   │   │   └── auth.css               # Layout authentification
│   │   ├── views/                     # Styles spécifiques aux vues
│   │   │   ├── home.css               # Vue principale
│   │   │   ├── dashboard.css          # Tableaux de bord
│   │   │   ├── courses.css            # Vues des cours
│   │   │   ├── quiz.css               # Vues des quiz
│   │   │   └── profile.css            # Vues des profils
│   │   └── responsive.css             # Responsive design complet
│   │
│   ├── js/                            # JavaScript complet du thème
│   │   ├── theme.js                   # Contrôleur principal du thème
│   │   ├── claymorphism.js            # Effets claymorphism
│   │   ├── animations.js              # Toutes les animations
│   │   ├── components.js              # Comportements de tous les composants
│   │   ├── layouts.js                 # Comportements des layouts
│   │   └── responsive.js              # Comportement responsive
│   │
│   ├── icons/                         # Toutes les icônes corporate
│   │   ├── svg/                       # Icônes SVG
│   │   │   ├── admin/                 # Icônes admin
│   │   │   ├── courses/               # Icônes cours
│   │   │   ├── users/                 # Icônes utilisateurs
│   │   │   ├── navigation/            # Icônes navigation
│   │   │   └── actions/               # Icônes actions
│   │   ├── fonts/                     # Fonts d'icônes
│   │   └── sprites.css                # Sprites CSS
│   │
│   ├── templates/                     # Templates de base pour toutes les vues
│   │   ├── base.html                  # Template de base principal
│   │   ├── admin-layout.html          # Layout administrateur
│   │   ├── student-layout.html        # Layout étudiant
│   │   ├── instructor-layout.html     # Layout formateur
│   │   ├── public-layout.html         # Layout public
│   │   └── auth-layout.html           # Layout authentification
│   │
│   └── docs/                          # Documentation du thème
│       ├── components.md              # Guide des composants
│       ├── customization.md           # Guide de personnalisation
│       └── theme-switching.md         # Guide changement de thème
│
├── views/                             # Vues indépendantes et portables
│   ├── Home/                          # Vue page d'accueil personnalisable
│   │   ├── HomeView.php               # Contrôleur vue
│   │   ├── home.html                  # Template HTML (utilise theme/)
│   │   ├── home-specific.css          # Styles spécifiques (si nécessaire)
│   │   ├── home.js                    # JavaScript spécifique
│   │   └── HomeModel.php              # Modèle données
│   │
│   ├── Admin/                         # Panel administrateur
│   │   ├── Dashboard/                 # Tableau de bord admin
│   │   │   ├── DashboardView.php
│   │   │   ├── dashboard.html         # Utilise theme/templates/admin-layout.html
│   │   │   ├── dashboard-specific.css # Si nécessaire
│   │   │   └── dashboard.js
│   │   ├── Users/                     # Gestion utilisateurs
│   │   │   ├── UsersView.php
│   │   │   ├── users.html
│   │   │   ├── users-specific.css
│   │   │   └── users.js
│   │   ├── Roles/                     # Gestion rôles
│   │   │   ├── RolesView.php
│   │   │   ├── roles.html
│   │   │   ├── roles-specific.css
│   │   │   └── roles.js
│   │   ├── HomeCustomizer/            # Personnalisation page d'accueil
│   │   │   ├── CustomizerView.php
│   │   │   ├── customizer.html
│   │   │   ├── customizer-specific.css
│   │   │   └── customizer.js
│   │   └── Settings/                  # Paramètres système
│   │       ├── SettingsView.php
│   │       ├── settings.html
│   │       ├── settings-specific.css
│   │       └── settings.js
│   │
│   ├── Auth/                          # Authentification
│   │   ├── Login/                     # Connexion
│   │   │   ├── LoginView.php
│   │   │   ├── login.html             # Utilise theme/templates/auth-layout.html
│   │   │   ├── login-specific.css
│   │   │   └── login.js
│   │   ├── Register/                  # Inscription
│   │   │   ├── RegisterView.php
│   │   │   ├── register.html
│   │   │   ├── register-specific.css
│   │   │   └── register.js
│   │   └── Profile/                   # Profil utilisateur
│   │       ├── ProfileView.php
│   │       ├── profile.html
│   │       ├── profile-specific.css
│   │       └── profile.js
│   │
│   ├── Courses/                       # Gestion cours
│   │   ├── List/                      # Liste des cours
│   │   ├── Create/                    # Création cours
│   │   ├── Edit/                      # Édition cours
│   │   └── View/                      # Affichage cours
│   │
│   ├── Student/                       # Interface étudiant
│   │   ├── Dashboard/                 # Tableau de bord étudiant
│   │   ├── MyCourses/                 # Mes cours
│   │   └── Progress/                  # Suivi progression
│   │
│   ├── Instructor/                    # Interface formateur
│   │   ├── Dashboard/                 # Tableau de bord formateur
│   │   ├── MyCourses/                 # Gestion mes cours
│   │   └── Students/                  # Gestion étudiants
│   │
│   └── Quiz/                          # Système de quiz
│       ├── Create/                    # Création quiz
│       ├── Take/                      # Passage quiz
│       └── Results/                   # Résultats quiz
│
├── assets/                            # Ressources statiques globales
│   ├── uploads/                       # Fichiers uploadés
│   ├── media/                         # Médias système
│   └── cache/                         # Cache des ressources
│
├── database/                          # Base de données
│   ├── elearning.db                   # Base SQLite embarquée
│   ├── migrations/                    # Scripts de migration
│   └── seeds/                         # Données d'exemple
│
└── api/                               # API REST (future extension)
    ├── v1/                           # Version 1 API
    └── middleware/                   # Middlewares API
```

## Fonctionnalités Principales

### Page d'Accueil Personnalisable
- **Bannières publicitaires** avec positions configurables via admin
- **Carrousel d'images/contenus** avec édition drag & drop
- **Cartes de cours** personnalisables (titre, description, image, prix)
- **Cartes formateurs** avec profils et spécialités
- **Zone d'annonces** dynamique avec système de priorités
- **Personnalisation complète** via panel administrateur
- **Styles centralisés** dans theme/css/views/home.css

### Système de Gestion des Rôles et Accès
- **Rôles principaux :** Super Admin, Admin, Formateur, Étudiant, Invité
- **Contrôle d'accès granulaire** par vue et par fonctionnalité
- **Matrice de permissions détaillée** pour chaque action de chaque vue
- **Logs d'audit** pour traçabilité des actions par rôle
- **Gestion hiérarchique** des permissions avec héritage

### Système de Thème Centralisé **[FONCTIONNALITÉ PRINCIPALE]**
- **Configuration unifiée** dans theme/config/ (JSON)
- **Changement complet de thème** en remplaçant uniquement le dossier theme/
- **Tous les composants centralisés** dans theme/css/components/
- **Layouts standardisés** dans theme/templates/
- **Personnalisation simple** via fichiers de configuration JSON
- **Cohérence visuelle automatique** sur toute l'application
- **Styles claymorphism modulaires** dans theme/css/claymorphism.css

### Gestion des Cours
- **Création et organisation** de cours par modules
- **Système de quiz et évaluations** avec notation automatique
- **Suivi de progression** personnalisé par étudiant
- **Gestion des contenus multimédia** centralisée

### Interfaces Utilisateur
- **Tableau de bord admin** avec contrôles d'accès (theme/css/layouts/admin.css)
- **Interface étudiant** personnalisée (theme/css/layouts/student.css)
- **Interface formateur** spécialisée (theme/css/layouts/instructor.css)
- **Système d'authentification** complet (theme/css/layouts/auth.css)

## Plan de Développement Modulaire
*Approche : Construction générale puis détails par vue*

### 🏗️ **Phase 1 : Configuration de Base + Système de Thème Centralisé**
**Objectif :** Infrastructure fondamentale et thème modulaire
**Livrables :**
- Configuration PHP 8.2 et workflow de développement
- Structure modulaire complète du projet avec autoloader PSR-4
- **Création complète du système de thème centralisé** (dossier theme/)
  - Configuration JSON complète (colors, typography, spacing, components, layouts, animations)
  - Variables CSS globales pour claymorphism
  - Templates de base réutilisables pour tous les layouts
  - Composants CSS modulaires (buttons, forms, cards, navigation, etc.)
  - JavaScript du thème pour animations et interactions
  - Icônes corporate centralisées
- Base de données SQLite embarquée avec tables de base
- Système de routage modulaire
- Classes de base (View, Controller, Model) avec intégration thème

### 🏠 **Phase 2 : Vue Principale (Home) - Structure Générale puis Détails**
**Objectif :** Page d'accueil avec intégration thème centralisé
**Approche Modulaire :**
1. **Structure générale** de la page d'accueil
   - Layout de base utilisant theme/templates/public-layout.html
   - Intégration des styles theme/css/views/home.css
   - Système de routage pour vue principale
2. **Puis implémentation des détails :**
   - Composants personnalisables (bannières, carrousel, cartes)
   - Système de gestion de contenu dynamique
   - Interface d'administration pour personnalisation

### 🔐 **Phase 3 : Authentification - Structure Générale puis Détails**
**Objectif :** Système de sécurité et contrôle d'accès
**Approche Modulaire :**
1. **Structure générale** du système d'authentification
   - Architecture des rôles et permissions
   - Classes de base pour sécurité
   - Layout auth utilisant theme/templates/auth-layout.html
2. **Puis implémentation des détails :**
   - Vues Login, Register, Profile avec thème intégré
   - Système de validation avancée
   - Logs d'audit et traçabilité
   - Middleware de sécurité

### 👑 **Phase 4 : Interface Admin - Structure Générale puis Détails**
**Objectif :** Panel administrateur complet
**Approche Modulaire :**
1. **Structure générale** du tableau de bord admin
   - Layout admin utilisant theme/templates/admin-layout.html
   - Navigation et structure globale
   - Système de permissions pour interface admin
2. **Puis implémentation des détails par vue :**
   - Vue gestion utilisateurs (structure → détails)
   - Vue gestion rôles et permissions (structure → détails)
   - Personnalisateur page d'accueil (structure → détails)
   - Vue paramètres système (structure → détails)

### 👥 **Phase 5 : Interfaces Utilisateur - Structure Générale puis Détails**
**Objectif :** Dashboards personnalisés par rôle
**Approche Modulaire :**
1. **Structure générale** des interfaces Student/Instructor
   - Layouts spécialisés (theme/templates/student-layout.html, instructor-layout.html)
   - Navigation adaptée par rôle
   - Système de permissions par interface
2. **Puis implémentation des détails :**
   - Tableaux de bord personnalisés (structure → détails)
   - Vues de gestion cours et progression (structure → détails)
   - Interfaces de suivi et statistiques (structure → détails)

### 📚 **Phase 6 : Gestion des Cours - Structure Générale puis Détails**
**Objectif :** Cœur métier e-learning
**Approche Modulaire :**
1. **Structure générale** du système de cours
   - Architecture modulaire des contenus
   - Système de permissions pour cours
   - Layout des vues cours utilisant thème centralisé
2. **Puis implémentation des détails par vue :**
   - Liste des cours (structure → filtres avancés → détails)
   - Création de cours (structure → éditeur → détails)
   - Édition de cours (structure → gestion contenus → détails)
   - Affichage cours (structure → lecteur → détails)

### 📝 **Phase 7 : Système d'Évaluation - Structure Générale puis Détails**
**Objectif :** Évaluations et quiz
**Approche Modulaire :**
1. **Structure générale** du système de quiz
   - Architecture des questions et notations
   - Système de permissions pour quiz
   - Layout des vues quiz utilisant thème
2. **Puis implémentation des détails par vue :**
   - Créateur de quiz (structure → types questions → détails)
   - Interface de passage (structure → lecteur → détails)
   - Résultats et rapports (structure → statistiques → détails)

### 🔌 **Phase 8 : API et Extensions Finales**
**Objectif :** Extensibilité et intégrations
- API REST sécurisée avec authentification
- Scripts de migration base de données (MySQL, PostgreSQL)
- Documentation technique complète
- Outils de maintenance et backup

## Règles de Développement

### Architecture Modulaire
- **MVC strict** avec séparation claire des responsabilités
- **Autoloader PSR-4** pour chargement automatique des classes
- **Namespaces** pour organisation modulaire
- **Classes de base** réutilisables avec intégration thème

### Système de Thème Centralisé **[RÈGLE PRINCIPALE]**
- **Centralisation absolue** dans le dossier theme/
- **Configuration JSON** pour tous les paramètres visuels
- **Variables CSS** pour consistance globale automatique
- **Modularité** permettant changement complet de thème
- **Composants réutilisables** dans toutes les vues via theme/
- **Aucun style en dur** dans les vues individuelles
- **Changement de thème** = remplacement du dossier theme/ uniquement

### Développement par Vue
- **Structure générale** de chaque vue en premier
- **Intégration du thème** centralisé obligatoire
- **Puis implémentation des détails** spécifiques
- **Portabilité** : chaque vue reste indépendante
- **Réutilisabilité** : utilisation des composants theme/

### Sécurité
- **Validation côté serveur et client** pour tous les inputs
- **Protection CSRF** sur tous les formulaires
- **Hashage sécurisé** des mots de passe (PHP password_hash)
- **Échappement HTML** pour prévention XSS
- **Requêtes préparées** pour protection SQL Injection
- **Logs d'audit** pour traçabilité complète

### Performance et Portabilité
- **Cache** des permissions et configurations
- **Optimisation** des requêtes base de données
- **Lazy loading** des ressources theme/ selon les besoins
- **Compression** des assets statiques
- **Configuration JSON** pour tous les paramètres
- **Chemins relatifs** exclusivement (__DIR__)
- **Base de données embarquée** SQLite par défaut

## Base de Données

### Tables Principales
- **users** - Informations utilisateurs
- **roles** - Définition des rôles système
- **permissions** - Permissions granulaires par vue/action
- **role_permissions** - Association rôles/permissions
- **courses** - Catalogue de cours et modules
- **enrollments** - Inscriptions cours par utilisateur
- **quiz** - Système d'évaluation et questions
- **quiz_attempts** - Tentatives et résultats
- **audit_logs** - Logs d'audit système complet
- **home_customization** - Configuration page d'accueil
- **theme_settings** - Paramètres du thème actuel

### Migration Multi-SGBD
- **SQLite** - Base par défaut embarquée (portabilité)
- **MySQL** - Migration avec scripts automatisés
- **PostgreSQL** - Support complet via abstraction PDO

## Design Claymorphism Centralisé

### Configuration Thème (theme/config/)
- **theme.json** - Paramètres principaux et métadonnées
- **colors.json** - Palette complète avec variantes
- **typography.json** - Configuration typographique complète
- **spacing.json** - Espacements, marges, padding standardisés
- **components.json** - Paramètres de tous les composants
- **layouts.json** - Configuration des layouts par rôle
- **animations.json** - Paramètres des animations claymorphism

### Caractéristiques Visuelles Centralisées
- **Soft shadows** et effets de profondeur configurables
- **Gradients subtils** et textures douces personnalisables
- **Palette de couleurs** entièrement configurable via JSON
- **Typographie** modulaire et professionnelle
- **Icônes corporate** centralisées et organisées
- **Animations** claymorphism fluides et élégantes

### Modularité du Thème **[AVANTAGE PRINCIPAL]**
- **Changement complet** en remplaçant le dossier theme/
- **Personnalisation rapide** via fichiers JSON uniquement
- **Consistance automatique** sur toutes les vues
- **Responsive design** intégré et configurable
- **Maintenance simplifiée** : un seul endroit pour tout le visuel
- **Évolutivité** : nouveau thème = nouveau dossier theme/

## Avantages de l'Architecture

### Pour les Développeurs
- **Modularité** : chaque vue est indépendante et portable
- **Thème centralisé** : modification visuelle simplifiée
- **Standards** : architecture MVC claire et documentée
- **Sécurité** : système de permissions granulaire
- **Performance** : optimisations intégrées

### Pour les Utilisateurs Finaux
- **Personnalisation** : interface adaptable via admin
- **Performance** : chargement rapide et optimisé
- **Sécurité** : protection des données et audit complet
- **Évolutivité** : plateforme extensible et configurable

### Pour le Déploiement
- **Portabilité** : fonctionne partout (XAMPP, cloud, serveurs)
- **Configuration** : paramètres JSON simples
- **Maintenance** : structure claire et documentée
- **Évolution** : changement de thème sans impact code

## Déploiement

### Compatibilité Universelle
- **XAMPP** - Installation immédiate, zéro configuration
- **Serveurs mutualisés** - Compatible hébergeurs standard
- **VPS/Dédié** - Déploiement serveur complet
- **Cloud** - Adapté environnements cloud (AWS, Azure, etc.)

### Configuration Minimale
- **PHP 8.0+** avec extensions PDO, JSON, Session
- **Serveur web** Apache/Nginx avec mod_rewrite
- **Permissions** lecture/écriture sur database/, assets/uploads/, assets/cache/
- **HTTPS recommandé** pour environnement production

### Installation Simple
1. Dézipper l'archive dans le répertoire web
2. Configurer les permissions sur les dossiers database/ et assets/
3. Accéder à l'URL dans le navigateur
4. Le système s'initialise automatiquement avec SQLite
5. Connexion admin par défaut : admin/admin123

**La plateforme est prête à fonctionner immédiatement après déploiement !**