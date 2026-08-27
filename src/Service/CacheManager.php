<?php
namespace Habeuk\HbkSymfony\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CacheManager {

  private int $ttl = 86400;

  // TODO il faudra mettre en place les tags. Cela permettra d'effacer des groupes de données.
  public function __construct(#[Autowire(service: 'app.data_cache_filesystem')] private readonly CacheInterface $cache,
    #[Autowire(service: 'app.data_cache_filesystem')] private readonly CacheItemPoolInterface $pool,
    #[Autowire('%kernel.debug%')] private readonly bool $isDebug) {}

  /**
   * Récupère une valeur depuis le cache ou l'exécute via le callback.
   * En mode debug (dev), le cache est bypassé pour faciliter le développement.
   */
  public function get(string $key, callable $callback, ?int $ttl = null): mixed {
    // En développement → on bypass toujours le cache
    if ($this->isDebug) {
      return $callback();
    }
    if ($ttl === null)
      $ttl = $this->ttl;
    return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl): mixed {
      $item->expiresAfter($ttl);
      return $callback();
    });
  }

  /**
   * Supprime une clé spécifique du cache.
   * Ne fait rien en mode debug.
   */
  public function delete(string $key): void {
    if (! $this->isDebug) {
      $this->cache->delete($key);
    }
  }

  /**
   * Vide complètement le cache.
   * Ne fait rien en mode debug.
   */
  public function clear(): void {
    if ($this->isDebug) {
      return;
    }
    $this->pool->clear();
  }

  /**
   *
   * @param string $prefix
   * @param array<string, mixed> $params
   * @return string
   */
  public function key(string $prefix, array $params = []): string {
    if ($params === []) {
      return $prefix;
    }

    // Utilisation de json_encode + hash pour plus de fiabilité et performance
    $hash = hash('xxh3', json_encode($params, JSON_THROW_ON_ERROR));

    return $prefix . '_' . $hash;
  }
}