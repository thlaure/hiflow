# Test technique back-end Hiflow

## Objectif
Développement d'une petite API REST pour enregistrer un client ainsi que ses restaurants.

## Fonctionnalités
L'API doit fournir un unique point d'accès permettant l'ajout d'un client. Un client est caractérisé par les éléments suivants :
- ID
- Nom
- Numéro SIREN
- Contact
- Email
- Téléphone
- Liste des restaurants

La liste des restaurants d'un client représente l'ensemble des adresses de la chaîne. Chaque adresse est composée de :

- Numéro & Voie
- Code postal
- Ville
- Pays

Pour des raisons techniques, l'enregistrement des restaurants en base de données ne peut pas se faire de manière synchrone avec la création du client. Afin d'optimiser le temps de réponse du point d'accès, le client est d'abord créé, puis la liste des adresses est peuplée de manière asynchrone.

## Fonctionnalités supplémentaires
- Ajout d'un système de logs avec Monolog
- Rédaction de tests unitaires pour le contrôleur et le job d'ajout des restaurants
- Gestion des erreurs et des succès avec les codes HTTP correspondants ([Wikipédia](https://fr.wikipedia.org/wiki/Liste_des_codes_HTTP))

## Modélisation

### UML
**Client**
- name: string
- siren: string
- contact: string
- email: string
- phone: string
- Restaurants: List\<Restaurant>

**Restaurant**
- route: string
- postalCode: string
- city: string
- country: string
- latitude: float
- longitude: float

### MLD
**clients**
- **PK** id INT NOT NULL AUTOINCREMENT
- name VARCHAR(255) NOT NULL
- siren VARCHAR(255) NOT NULL
- contact VARCHAR(255) NOT NULL
- email VARCHAR(255) NOT NULL
- phone VARCHAR(255) NOT NULL

**restaurants**
- **PK** id INT NOT NULL AUTOINCREMENT
- route VARCHAR(255) NOT NULL
- postal_code  VARCHAR(255) NOT NULL
- city VARCHAR(255) NOT NULL
- country VARCHAR(255) NOT NULL
- latitude DECIMAL NOT NULL
- logitude DECIMAL NOT NULL
- **FK** client_id INT NOT NULL

## Pour lancer le projet
1. Lancer la commande ```composer install```
2. Lancer le serveur avec ```php artisan serve```
3. Dans un terminal différent, lancer la commande ```php artisan queue:listen``` pour gérer la queue


## Idées pour améliorer le projet
- Une interface avec formulaire pour manipuler l'API
- Utiliser autre chose que la base de données pour gérer la queue pour des raisons de performances
- Utiliser des branches séparées plutôt que de commit directement sur la branche main