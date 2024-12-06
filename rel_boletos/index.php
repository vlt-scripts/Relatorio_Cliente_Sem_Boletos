<?php
// INCLUDE ADDON FUNCTIONS
include('addons.class.php');

// SESSION AND LOGIN CHECK
session_name('mka');
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['MKA_Logado'])) exit('Access denied... <a href="/admin/">Login</a>');

$manifestTitle = $Manifest->{'name'} ?? '';
$manifestVersion = $Manifest->{'version'} ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR" class="has-navbar-fixed-top" style="margin-top: 20px;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MK - AUTH :: <?= htmlspecialchars($manifestTitle . " - V " . $manifestVersion); ?></title>
    
    <!-- External Stylesheets -->
    <link href="../../estilos/mk-auth.css" rel="stylesheet">
    <link href="../../estilos/font-awesome.css" rel="stylesheet">
	<link href="../../estilos/bi-icons.css" rel="stylesheet" type="text/css" />
	
	<!-- External Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="../../scripts/jquery.js"></script>
    <script src="../../scripts/mk-auth.js"></script>
</head>
    <style>
        .container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
    </style>
	
<body class="bg-white h-full">
    <?php include('../../topo.php'); ?>

    <div class="container mx-auto px-4 py-6">
        <nav class="mb-6 text-center">
            <ol class="inline-flex items-center space-x-2">
                <li><a href="#" class="text-blue-600 hover:text-blue-800">ADDON</a></li>
                <li class="text-gray-500">
                    <?= htmlspecialchars($manifestTitle . " - V " . $manifestVersion); ?>
                </li>
            </ol>
        </nav>

        <?php include('config.php'); ?>

        <?php if ($acesso_permitido): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <form id="searchForm" method="GET" class="p-6 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="flex-grow min-w-[300px]">
                            <label for="search" class="block mb-2 font-bold text-gray-700">Buscar Cliente:</label>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                placeholder="Digite o Nome do Cliente" 
                                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition"
                            >
                                Buscar
                            </button>
                            <button 
                                type="button" 
                                onclick="clearSearch()" 
                                class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition"
                            >
                                Limpar
                            </button>
                            <button 
                                type="button" 
                                onclick="sortTable(1)" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition"
                            >
                                Ordenar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-white">Nome do Cliente</th>
                                <th class="px-4 py-3 text-center text-white">Data de Pagamento</th>
                                <th class="px-4 py-3 text-center text-white">Boleto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Search condition logic (same as original)
                            $searchCondition = '';
                            if (!empty($_GET['search'])) {
                                $search = strtolower(mysqli_real_escape_string($link, $_GET['search']));
                                if ($search === 'carne') {
                                    $searchCondition = " AND LOWER(c.tipo_cob) LIKE 'carne'";
                                } elseif ($search === 'titulo') {
                                    $searchCondition = " AND LOWER(c.tipo_cob) LIKE 'titulo'";
                                } else {
                                    $searchCondition = " AND (LOWER(c.login) LIKE '%$search%' OR LOWER(c.nome) LIKE '%$search%')";
                                }
                            }

                            // Query (same as original)
                            $query = "SELECT c.uuid_cliente, c.nome, c.tipo_cob, MAX(l.datapag) AS datapag
                                      FROM sis_cliente c
                                      LEFT JOIN sis_lanc l ON c.login = l.login
                                      WHERE c.parc_abertas = 0 
                                      AND c.cli_ativado = 's' 
                                      AND (c.tipo_cob LIKE 'carne' OR c.tipo_cob LIKE 'titulo')
                                      AND c.isento LIKE 'nao'
                                      AND c.tit_abertos = 0" . $searchCondition .
                                " GROUP BY c.uuid_cliente, c.nome
                                      ORDER BY datapag DESC";

                            $result = mysqli_query($link, $query);
                            $resultCount = 0;

                            if ($result) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $resultCount++;
                                    $nome_por_num_titulo = "Nome do Cliente: " . $row['nome'] . " - UUID: " . $row['uuid_cliente'];

                                    $rowNumber++;
                                    $nomeClienteClass = ($rowNumber % 2 == 0) ? 'bg-yellow-50' : 'bg-white';
                                    $tipoBoleto = $row['tipo_cob'] == 'titulo' ? 'Titulo' : 'Carne';

                                    echo "<tr class='$nomeClienteClass hover:bg-blue-100 transition'>";
                                    echo "<td class='px-4 py-3 border-b'>";
                                    echo "<a href='../../cliente_det.hhvm?uuid=" . $row['uuid_cliente'] . "' target='_blank' class='text-blue-600 hover:underline font-semibold font-bold'>" . 
                                    "<i class='fa fa-user mr-2'></i>" . $row['nome'] . "</a>";
                                    echo "</td>";

                                    echo "<td class='px-4 py-3 border-b text-center text-green-600 font-bold'>";
                                    echo "<i class='fa fa-calendar mr-2'></i>";
                                    echo ($row['datapag'] ? date('d/m/Y', strtotime($row['datapag'])) : 'N/A');
                                    echo "</td>";

                                    echo "<td class='px-4 py-3 border-b text-center'>";
                                    echo "<a href=\"javascript:void(0);\" onclick=\"searchByTipoCob('" . urlencode($tipoBoleto) . "')\" class='text-blue-600 hover:underline font-bold'>$tipoBoleto</a>";
                                    echo "</td>";

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center py-4'>Nenhum registro encontrado.</td></tr>";
                            }
                            ?>
                        </tbody>
						                <?php if ($resultCount > 0): ?>
                    <div class="p-4 bg-gray-50 text-center">
                        <h3 class="text-lg">
                            <span class="font-bold">Clientes sem Boletos:</span> 
                            <span class="text-blue-600 font-bold"><?= $resultCount ?></span>
                        </h3>
                    </div>
                <?php endif; ?>
                    </table>
                </div>


            </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                Acesso não permitido!
            </div>
        <?php endif; ?>
    </div>

    <?php include('../../baixo.php'); ?>
    <?php include('../../rodape.php'); ?>
    <script src="../../menu.js.php"></script>

    <script>
    function clearSearch() {
        document.getElementById('search').value = '';
        document.forms['searchForm'].submit();
    }

    function searchByTipoCob(tipoCob) {
        document.getElementById('search').value = tipoCob;
        document.forms['searchForm'].submit();
    }

    function sortTable(columnIndex) {
        var table = document.querySelector('table');
        var rows = Array.from(table.querySelectorAll('tbody tr'));
        var dir = table.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
        
        rows.sort((a, b) => {
            let x = a.getElementsByTagName('TD')[columnIndex].textContent.trim().toLowerCase();
            let y = b.getElementsByTagName('TD')[columnIndex].textContent.trim().toLowerCase();
            return dir === 'asc' ? x.localeCompare(y) : y.localeCompare(x);
        });

        // Reinsert sorted rows
        let tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
        table.setAttribute('data-sort-dir', dir);
    }
    </script>
</body>
</html>