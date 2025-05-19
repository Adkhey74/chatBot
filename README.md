# Setup

1. Faire une copie du .env nommée .env.local

cp .env .env.local

2. Générer l'APP_SECRET puis copier le code obtenu dans le .env.local

openssl rand -hex 32

3. Configurer l'accès à la BDD en mettant vos propres informations dans le .env.local

DATABASE_URL="mysql://username:password@127.0.0.1:3306/db_name?serverVersion=8.0.40&charset=utf8

4. Installer les dépendances du projet

composer install

5. Créer la BDD

php bin/console d:d:c

6. Charger les migrations dans la BDD

php bin/console d:m:m

7. Ajout des fixtures pour un jeu de données (pas pour le moment)

php bin/console d:f:l

8. Générer les clés JsonWebToken (penser à déplacer les valeurs du .env dans le .env.local)

php bin/console lexik:jwt:generate-keypair

9. Lancer le serveur

symfony serve --no-tls

10. Aller sur le Swagger

localhost:8000/api
