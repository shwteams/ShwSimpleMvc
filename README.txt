J'aimerais par apprendre a toute personnes qui debut en programmation une methode de travail tres simple copier un peu sur le principe du MVC, cette technique de programmation permettra a tout developpeur de ne pas donner plus d'une fois un workflow ou un code. Ce qui lui permettra de penser qu'au amelioration de son code apres l'avoir ecris. Aussi, presenter comme le reutiliser dans d'autre projet.
je tiens a rappeler que ceux-ci es une methode pour debutant en programmation qui desir aller plus vite dans le developpement de leur application.
############################################################################################################################################################METHODE DE FONCTIONNEMENT###################################################################################################################################################################################
1- creer un fichier nommer db.php qui permettra de concerver toutes les fonctions de votre application, et la connexion a la base de donnee
2- Diviser votre application en module
3- Creer des dossiers pour chaque modules, les noms doivent etre de preference court et simple
4- Apres avoir creer le model html de votre application et bien designer, decouper le de tel sort a garder le fond pour afficher vos formulaire, etat et / ou graphs
5- Recuperer le menu aussi et le mettre dans un fichier nommÚ menu puis laisser le a la racine de votre application. Parreil pour votre fichier index.php
6- Ajouter tout les fichiers en rapport avec ce module dans le dossier du module que vous avez creer.
	-En effet j'aimerais que vous voyer le module comme une petite application dans votre application. Du coup, votre application celon ca taille sera contrituer de plusieur module.
7- La nommenclature de tout les fichiers formulaire doivent etre pre-fixe "form". Exemple : form_courrier.php. c'est votre vu. Idem pour les fichiers de graph qui doivent etre pre-fixer de "graph" suivit du nom que vous voudriez bien lui attribuer
8- Vos pas de soumission doivent etre pre-fixer de "script". Ce sont vos controller
NB: rappelons que ici, le fichier db.php est notre model mais selon notre approche, de reutilisabiliter et de generalisation des fonctions il ne doit pas contenir de requete sql. Sauf en cas de necessiter
9- Nous allons pour finir utiliser un systeme d'inclision de page
10- N'oubliez surtout pas de creer le dossier data_base dans lequel vous aller ajouter votre script sql de creation de base de donner
Pour faire plus simple nous allons donner un exemple, c'est l'exemple de saph_courrier, le projet sur lequel j'ai debuter avec cette methode de travail
