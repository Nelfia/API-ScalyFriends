<?php

declare(strict_types=1);

namespace vues;

use peps\core\Cfg;

?>

<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<title><?= Cfg::get('appTitle') ?></title>
</head>

<body>
	<main>
		<div class="category">
			<a href="/">Accueil</a> &gt; Oups !
		</div>
		<img src="/assets/img/error404.png" alt="Oups !" />
	</main>
	<footer></footer>
</body>

</html>