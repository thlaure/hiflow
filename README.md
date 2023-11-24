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

API de geocoding choisie : [Nominatim](https://nominatim.org/release-docs/develop/api/Search/#output-details)

Pourquoi ?
- Légèreté
- Simplicité d'utilisation
- Pas besoin de clé API pour tester dans ce contexte 

## Fonctionnalités supplémentaires
- Ajout d'un système de logs avec Monolog
- Rédaction de tests fonctionnels pour le contrôleur et le job d'ajout des restaurants
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
2. Installer les containers avec Sail : ```./vendor/bin/sail build```
3. Démarrer l'environnement : ```./vendor/bin/sail up```

Dans un terminal différent : 
1. Installer la base de données et déployer le schéma : ```./vendor/bin/sail php artisan migrate```
2. Lancer la commande ```./vendor/bin/sail php artisan queue:listen``` pour gérer la queue
3. Lancer les tests fonctionnels avec la commande ```./vendor/bin/sail php artisan test```

## Pour tester
Pour tester l'API, une [collection Postman](./Test%20Hiflow.postman_collection.json) est mise à disposition :
```
{
	"info": {
		"_postman_id": "24d49076-f642-40af-b5d5-953cb5ab355a",
		"name": "Test Hiflow",
		"schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
		"_exporter_id": "11365470"
	},
	"item": [
		{
			"name": "Clients",
			"protocolProfileBehavior": {
				"disableBodyPruning": true
			},
			"request": {
				"method": "GET",
				"header": [],
				"body": {
					"mode": "raw",
					"raw": "",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "http://localhost/api/clients",
					"protocol": "http",
					"host": [
						"localhost"
					],
					"path": [
						"api",
						"clients"
					]
				}
			},
			"response": []
		},
		{
			"name": "Clients",
			"protocolProfileBehavior": {
				"disabledSystemHeaders": {
					"accept": true
				}
			},
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "Accept",
						"value": "application/json",
						"type": "text"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\n    \"name\": \"Nom du client\",\n    \"siren\": \"123456788\",\n    \"contact\": \"Personne de contact\",\n    \"email\": \"client2@example.com\", \n    \"phone\": \"0123456789\",\n    \"restaurants\": [\n        {\n            \"route\": \"88 chemin du châtaignier\",\n            \"postal_code\": \"83260\",\n            \"city\": \"La Crau\",\n            \"country\": \"France\"\n        },\n        {\n            \"route\": \"50 chemin du châtaignier\",\n            \"postal_code\": \"83260\",\n            \"city\": \"La Crau\",\n            \"country\": \"France\"\n        }\n    ]\n}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "http://localhost/api/clients",
					"protocol": "http",
					"host": [
						"localhost"
					],
					"path": [
						"api",
						"clients"
					]
				}
			},
			"response": []
		}
	]
}
``````

## Idées pour améliorer le projet
- Une interface avec formulaire pour manipuler l'API
- Utiliser autre chose que la base de données pour gérer la queue pour des raisons de performances
- Utiliser des branches séparées plutôt que de commit directement sur la branche main