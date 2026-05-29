<?php
// api/index.php — Main Router

require_once 'config/database.php';

$method   = $_SERVER['REQUEST_METHOD'];
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));

// Determine resource and optional ID
// Expected: /api/{resource} or /api/{resource}/{id}
$resource = null;
$id       = null;

foreach ($segments as $i => $seg) {
    if ($seg === 'api' && isset($segments[$i + 1])) {
        $resource = $segments[$i + 1];
        if (isset($segments[$i + 2]) && is_numeric($segments[$i + 2])) {
            $id = (int) $segments[$i + 2];
        }
        break;
    }
}

switch ($resource) {
    case 'candidates':  require 'endpoints/candidates.php';  break;
    case 'voters':      require 'endpoints/voters.php';      break;
    case 'positions':   require 'endpoints/positions.php';   break;
    case 'votes':       require 'endpoints/votes.php';       break;
    case 'results':     require 'endpoints/results.php';     break;
    case 'settings':    require 'endpoints/settings.php';    break;
    default:
        sendResponse(false, "Endpoint not found. Available: /api/candidates, /api/voters, /api/positions, /api/votes, /api/results, /api/settings", null, 404);
}
