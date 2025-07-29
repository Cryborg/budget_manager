# 💰 Budget Manager

Un gestionnaire de budget personnel développé avec Laravel et Filament, permettant de suivre et prévoir l'évolution de vos comptes bancaires.

## ✨ Fonctionnalités

### 📊 Vue d'ensemble
- **Tableau de bord interactif** avec graphique d'évolution des comptes
- **Prévisions financières** sur 12, 24, 36 mois ou 5 ans
- **Statistiques en temps réel** des revenus, dépenses et soldes

### 🏦 Gestion des comptes
- **Multi-banques** : gérez plusieurs établissements bancaires
- **Multi-comptes** : suivez tous vos comptes (courant, épargne, etc.)
- **Ajustements de solde** : corrigez les écarts avec la réalité

### 💸 Transactions
- **Revenus** : salaires, primes, revenus annuels (31/12), etc.
- **Dépenses** : loyer, courses, abonnements, etc.
- **Virements** : entre vos différents comptes
- **Fréquences flexibles** : ponctuel, quotidien, hebdomadaire, mensuel, annuel

### 📈 Graphique d'évolution
- **Visualisation interactive** : cliquez sur la légende pour masquer/afficher des comptes
- **Calculs intelligents** : prise en compte de toutes les fréquences de transactions
- **Ligne de total** : vue consolidée de votre patrimoine
- **Responsive** : adapté à tous les écrans

## 🚀 Installation

### Prérequis
- PHP 8.1 ou supérieur
- Composer
- Node.js & NPM
- SQLite (ou autre base de données supportée par Laravel)

### Étapes d'installation

1. **Cloner le repository**
```bash
git clone [url-du-repo]
cd budget_simulator/budget-manager
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Configurer l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurer la base de données**
```bash
# Créer le fichier SQLite
touch database/database.sqlite

# Lancer les migrations
php artisan migrate
```

5. **Installer les dépendances JavaScript**
```bash
npm install
npm run build
```

6. **Créer un utilisateur admin**
```bash
php artisan make:filament-user
```

7. **Lancer le serveur de développement**
```bash
php artisan serve
```

Accédez à l'application sur http://localhost:8000/admin

## 📱 Utilisation

### Premier démarrage
1. Créez vos banques
2. Ajoutez vos comptes bancaires avec leur solde actuel
3. Saisissez vos revenus et dépenses récurrents
4. Consultez le graphique pour visualiser vos prévisions

### Gestion des transactions
- **Revenus/Dépenses annuels** : saisissez la date exacte (ex: 31/12) pour qu'ils soient comptés le bon mois
- **Virements automatiques** : planifiez vos épargnes mensuelles
- **Dates de début/fin** : gérez les périodes d'activité de chaque transaction

### Ajustements de solde
Utilisez cette fonctionnalité pour :
- Corriger les écarts entre vos prévisions et la réalité
- Prendre en compte des opérations exceptionnelles
- Recalibrer vos projections

## 🔧 Configuration avancée

### Base de données
Pour utiliser MySQL ou PostgreSQL au lieu de SQLite :
1. Modifiez le fichier `.env`
2. Créez la base de données
3. Relancez les migrations

## 📄 Licence

Ce projet est sous licence MIT.

---

Développé avec ❤️ pour une meilleure gestion financière personnelle
