<?php

declare(strict_types=1);
namespace SourceBroker\T3api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SourceBroker\T3api\Dispatcher\Bootstrap;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class T3apiRequestResolver
 */
class T3apiRequestResolver implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_array($request->getQueryParams()) && array_key_exists('t3apiResource', $request->getQueryParams())) {
            return GeneralUtility::makeInstance(ObjectManager::class)
                ->get(Bootstrap::class)
                ->process($this->cleanupRequest($request));
        }

        return $handler->handle($request);
    }

    /**
     * Removes `t3apiResource` query parameter as it may break further functionality.
     * This parameter is needed only to reach a handler - further processing should not rely on it.
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function cleanupRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $cleanedQueryParams = $request->getQueryParams();
        unset($cleanedQueryParams['t3apiResource']);

        return $request->withQueryParams($cleanedQueryParams);
    }
}
