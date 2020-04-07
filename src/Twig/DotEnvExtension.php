<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DotEnvExtension extends AbstractExtension{

	public function getFunctions(): array {
		return [
			new TwigFunction(
				'dotenv',
				[$this, 'getenv'],
				['is_safe'=>['html']]
			)
		];
	}

	public function getEnv(string $varName): ?string {
		return $_ENV[$varName] ?? null;
	}
}