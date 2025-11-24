<?php
// --- PARTIE BACKEND (Simulation des données et Logique) ---

// 1. Mock Data (Simulation d'une CMDB ou retour d'API)
$servers = [
    ['hostname' => 'srv-prod-db-01', 'ip' => '192.168.1.10', 'env' => 'Production', 'filebeat' => true, 'zabbix' => true, 'xlr' => true],
    ['hostname' => 'srv-prod-app-01', 'ip' => '192.168.1.11', 'env' => 'Prod', 'filebeat' => true, 'zabbix' => true, 'xlr' => false],
    ['hostname' => 'srv-preprod-ws-01', 'ip' => '192.168.2.50', 'env' => 'Preprod', 'filebeat' => true, 'zabbix' => false, 'xlr' => true],
    ['hostname' => 'srv-dev-sandbox', 'ip' => '10.0.0.5', 'env' => 'Devellopement', 'filebeat' => false, 'zabbix' => false, 'xlr' => false],
    ['hostname' => 'srv-homol-front', 'ip' => '192.168.3.20', 'env' => 'Homologation', 'filebeat' => true, 'zabbix' => true, 'xlr' => true],
    ['hostname' => 'srv-assembl-ci', 'ip' => '192.168.4.10', 'env' => 'Assemblage', 'filebeat' => false, 'zabbix' => true, 'xlr' => true],
];

// Liste des environnements demandés
$environnements = ['Production', 'Assemblage', 'Homologation', 'Devellopement', 'Preprod', 'Prod'];

// 2. Récupération des filtres
$search = $_GET['search'] ?? '';
$filterEnv = $_GET['env'] ?? '';
// Note: Pour les checkbox, si non coché = non envoyé.
// Ici on filtre seulement si l'utilisateur demande spécifiquement de voir ceux qui ont l'agent actif
$reqFilebeat = isset($_GET['filebeat']); 
$reqZabbix = isset($_GET['zabbix']);
$reqXlr = isset($_GET['xlr']);

// 3. Logique de Filtrage
$results = array_filter($servers, function($server) use ($search, $filterEnv, $reqFilebeat, $reqZabbix, $reqXlr) {
    // Filtre Recherche Texte (Hostname ou IP)
    if ($search && strpos($server['hostname'], $search) === false && strpos($server['ip'], $search) === false) {
        return false;
    }
    // Filtre Environnement
    if ($filterEnv && $server['env'] !== $filterEnv) {
        return false;
    }
    // Filtres Agents (Si coché, on ne veut que ceux qui l'ont)
    /* Décommenter ces lignes si cocher la case signifie "Doit obligatoirement avoir l'agent"
       Pour l'instant, je laisse l'affichage passif, c'est souvent plus utile en DevOps de voir l'état réel.
    */
    return true;
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Fleet Manager | DevOps Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-control-lg { border-radius: 30px; }
        .card { border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .status-dot { height: 10px; width: 10px; background-color: #bbb; border-radius: 50%; display: inline-block; margin-right: 5px;}
        .status-ok { background-color: #28a745; box-shadow: 0 0 5px #28a745; }
        .status-ko { background-color: #dc3545; opacity: 0.5; }
        .badge-env { min-width: 100px; }
    </style>
</head>
<body>

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="mb-3"><i class="fas fa-server"></i> CMDB & Health Check</h1>
            <p class="mb-4 text-light opacity-75">Recherche et statut des agents d'infrastructure</p>
            
            <div class="search-container">
                <form action="" method="GET" class="row g-3 justify-content-center align-items-end">
                    
                    <div class="col-12 mb-3">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-0 text-primary"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-0" placeholder="Rechercher un serveur (hostname, IP)..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-warning fw-bold" type="submit">SCAN</button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <select name="env" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Tous les environnements --</option>
                            <?php foreach($environnements as $env): ?>
                                <option value="<?= $env ?>" <?= $filterEnv === $env ? 'selected' : '' ?>><?= $env ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-8 text-white d-flex justify-content-around align-items-center">
                        <span class="small">Agents surveillés :</span>
                        <span class="badge bg-primary"><i class="fas fa-file-alt"></i> Filebeat</span>
                        <span class="badge bg-danger"><i class="fas fa-heartbeat"></i> Zabbix</span>
                        <span class="badge bg-info text-dark"><i class="fas fa-robot"></i> XLR</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-muted">Résultats (<?= count($results) ?> serveurs)</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Hostname</th>
                            <th>IP Address</th>
                            <th>Environnement</th>
                            <th class="text-center">Filebeat</th>
                            <th class="text-center">Zabbix</th>
                            <th class="text-center">XLR Agent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($results) > 0): ?>
                            <?php foreach($results as $srv): ?>
                            <tr>
                                <td class="fw-bold text-primary">
                                    <i class="fas fa-hdd me-2 text-secondary"></i><?= $srv['hostname'] ?>
                                </td>
                                <td class="font-monospace"><?= $srv['ip'] ?></td>
                                <td>
                                    <?php 
                                        $badgeColor = match($srv['env']) {
                                            'Production', 'Prod' => 'bg-danger',
                                            'Preprod' => 'bg-warning text-dark',
                                            'Devellopement' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeColor ?> badge-env"><?= $srv['env'] ?></span>
                                </td>
                                
                                <td class="text-center">
                                    <?php if($srv['filebeat']): ?>
                                        <i class="fas fa-check-circle text-success fa-lg" title="Actif"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-muted opacity-25" title="Inactif/Manquant"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($srv['zabbix']): ?>
                                        <i class="fas fa-check-circle text-success fa-lg" title="Actif"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-warning" title="Problème"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($srv['xlr']): ?>
                                        <i class="fas fa-check-circle text-success fa-lg" title="Actif"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-muted opacity-25"></i>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border"><i class="fas fa-terminal"></i> SSH</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-3x mb-3 d-block"></i>
                                    Aucun serveur trouvé pour ces critères.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4 mt-5 small">
        &copy; <?= date('Y') ?> OpsTeam Tools - Internal Use Only
    </footer>

</body>
</html>
