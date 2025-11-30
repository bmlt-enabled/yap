<?php

use Illuminate\Support\Facades\Route;

/**
 * Test to ensure OpenAPI/Swagger documentation matches actual Laravel routes
 *
 * This test validates that:
 * 1. All documented API paths in OpenAPI annotations actually exist as routes
 * 2. HTTP methods match between documentation and routes
 * 3. Route paths match exactly (accounting for parameter syntax differences)
 *
 * Run this test to catch documentation drift: php artisan test --filter OpenApiRouteValidation
 */
test('openapi annotations match actual routes', function () {
    // Get all actual routes from Laravel
    $actualRoutes = getActualApiRoutes();

    // Get all documented routes from OpenAPI annotations
    $documentedRoutes = getDocumentedApiRoutes();

    $mismatches = [];
    $undocumented = [];
    $nonExistent = [];

    // Check each documented route exists in actual routes
    foreach ($documentedRoutes as $docRoute) {
        $found = false;
        foreach ($actualRoutes as $actualRoute) {
            if (routesMatch($docRoute, $actualRoute)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $nonExistent[] = sprintf(
                "%s %s (documented in %s but route doesn't exist)",
                $docRoute['method'],
                $docRoute['path'],
                $docRoute['file']
            );
        }
    }

    // Check each actual route has documentation
    foreach ($actualRoutes as $actualRoute) {
        $found = false;
        foreach ($documentedRoutes as $docRoute) {
            if (routesMatch($docRoute, $actualRoute)) {
                $found = true;
                break;
            }
        }

        if (!$found && shouldBeDocumented($actualRoute)) {
            $undocumented[] = sprintf(
                "%s %s (route exists but not documented)",
                $actualRoute['method'],
                $actualRoute['path']
            );
        }
    }

    // Build detailed error message
    if (!empty($nonExistent) || !empty($undocumented)) {
        $errorMessage = "\n\n╔══════════════════════════════════════════════════════════╗\n";
        $errorMessage .= "║  OpenAPI Documentation Validation Failed                ║\n";
        $errorMessage .= "╚══════════════════════════════════════════════════════════╝\n\n";

        if (!empty($nonExistent)) {
            $errorMessage .= "📄 DOCUMENTED BUT DON'T EXIST (" . count($nonExistent) . "):\n";
            $errorMessage .= "   These routes are in @OA annotations but don't exist in routes/api.php\n\n";
            foreach ($nonExistent as $route) {
                $errorMessage .= "   ❌ $route\n";
            }
            $errorMessage .= "\n";
        }

        if (!empty($undocumented)) {
            $errorMessage .= "🔍 EXIST BUT NOT DOCUMENTED (" . count($undocumented) . "):\n";
            $errorMessage .= "   These routes exist but have no @OA annotations\n\n";
            foreach ($undocumented as $route) {
                $errorMessage .= "   ⚠️  $route\n";
            }
            $errorMessage .= "\n";
        }

        $errorMessage .= "📊 Summary:\n";
        $errorMessage .= "   Total routes documented: " . count($documentedRoutes) . "\n";
        $errorMessage .= "   Total routes actual: " . count($actualRoutes) . "\n";
        $errorMessage .= "\n";

        expect(empty($nonExistent) && empty($undocumented))
            ->toBeTrue($errorMessage);
    }

    // If we get here, all routes match
    expect(true)->toBeTrue("All routes match their documentation ✅");
})->group('api', 'documentation');

/**
 * Get all actual API routes from Laravel
 */
function getActualApiRoutes(): array
{
    $routes = [];
    $routeCollection = Route::getRoutes();

    foreach ($routeCollection as $route) {
        $uri = $route->uri();

        // Only include API v1 routes
        if (!str_starts_with($uri, 'api/v1/')) {
            continue;
        }

        // Skip certain routes
        if (str_contains($uri, 'documentation') ||
            str_contains($uri, 'openapi.json') ||
            str_contains($uri, 'sanctum')) {
            continue;
        }

        foreach ($route->methods() as $method) {
            // Skip HEAD and OPTIONS
            if (in_array($method, ['HEAD', 'OPTIONS'])) {
                continue;
            }

            $routes[] = [
                'method' => $method,
                'path' => '/' . $uri,
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        }
    }

    return $routes;
}

/**
 * Get all documented API routes from OpenAPI annotations
 */
function getDocumentedApiRoutes(): array
{
    $routes = [];
    $controllerPath = app_path('Http/Controllers');

    // Recursively find all controller files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($controllerPath)
    );

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());

            // Find all @OA\Get, @OA\Post, @OA\Put, @OA\Delete, @OA\Patch annotations
            $methods = ['Get', 'Post', 'Put', 'Delete', 'Patch'];

            foreach ($methods as $method) {
                // Match @OA\Method( ... path="/api/v1/..." ... )
                $pattern = '/@OA\\\\' . $method . '\s*\(\s*[^)]*path\s*=\s*"([^"]+)"[^)]*\)/s';

                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $path) {
                        // Only include API v1 routes
                        if (str_starts_with($path, '/api/v1/')) {
                            $routes[] = [
                                'method' => strtoupper($method),
                                'path' => $path,
                                'file' => basename($file->getPathname()),
                            ];
                        }
                    }
                }
            }
        }
    }

    return $routes;
}

/**
 * Check if two routes match (accounting for parameter syntax differences)
 * Laravel uses {param} while OpenAPI might use {param} or similar
 */
function routesMatch(array $route1, array $route2): bool
{
    // Methods must match
    if ($route1['method'] !== $route2['method']) {
        return false;
    }

    // Normalize paths for comparison
    $path1 = normalizePath($route1['path']);
    $path2 = normalizePath($route2['path']);

    return $path1 === $path2;
}

/**
 * Normalize a route path for comparison
 * Converts Laravel's {param} and OpenAPI's {param} to a common format
 */
function normalizePath(string $path): string
{
    // Convert to lowercase for case-insensitive comparison
    $path = strtolower($path);

    // Remove trailing slashes
    $path = rtrim($path, '/');

    // Normalize parameter syntax - convert {param?} to {param}
    $path = preg_replace('/\{([^}?]+)\?\}/', '{$1}', $path);

    return $path;
}

/**
 * Determine if a route should be documented
 * Some routes like login might not need full documentation
 */
function shouldBeDocumented(array $route): bool
{
    // Version endpoint is simple, might skip
    if ($route['path'] === '/api/v1/version') {
        return false;
    }

    // Upgrade endpoint is simple, might skip
    if ($route['path'] === '/api/v1/upgrade') {
        return false;
    }

    // All other API routes should be documented
    return true;
}
