<?php

session_start();
include 'funcion.php';

if (!isset($_SESSION['nombre']) || !isset($_SESSION['apellido'])) {
    header("Location: index.php");
    exit();
}

$conn = conexion();

$Total_clientes = $conn->query("SELECT COUNT(*) AS total_clientes FROM clientes")->fetch_assoc()['total_clientes'];
$Total_empleados = $conn->query("SELECT COUNT(*) AS total_empleados FROM empleados")->fetch_assoc()['total_empleados'];
$Total_proveedores = $conn->query("SELECT COUNT(*) AS total_proveedores FROM proveedores")->fetch_assoc()['total_proveedores'];
$Total_ventas = $conn->query("SELECT COUNT(*) AS total_ventas FROM ventas")->fetch_assoc()['total_ventas'];
$Total_compras = $conn->query("SELECT COUNT(*) AS total_compras FROM compras")->fetch_assoc()['total_compras'];
$Total_productos = $conn->query("SELECT COUNT(*) AS total_productos FROM productos")->fetch_assoc()['total_productos'];

$Ventas_cobradas = $conn->query("SELECT COUNT(*) AS cobradas FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Cobrada'")->fetch_assoc()['cobradas'];
$Ventas_pendientes = $conn->query("SELECT COUNT(*) AS pendientes FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Pendiente'")->fetch_assoc()['pendientes'];
$Ventas_canceladas = $conn->query("SELECT COUNT(*) AS canceladas FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Cancelada'")->fetch_assoc()['canceladas'];

$Compras_pagadas = $conn->query("SELECT COUNT(*) AS pagadas FROM compras c INNER JOIN estadoo e ON c.id_estado = e.id_estadoo WHERE e.nombre_del_estadoo = 'Pagada'")->fetch_assoc()['pagadas'];
$Compras_pendientes = $conn->query("SELECT COUNT(*) AS pendientes FROM compras c INNER JOIN estadoo e ON c.id_estado = e.id_estadoo WHERE e.nombre_del_estadoo = 'Pendiente'")->fetch_assoc()['pendientes'];
$Compras_canceladas = $conn->query("SELECT COUNT(*) AS canceladas FROM compras c INNER JOIN estadoo e ON c.id_estado = e.id_estadoo WHERE e.nombre_del_estadoo = 'Cancelada'")->fetch_assoc()['canceladas'];

$Metodo_efectivo = $conn->query("SELECT COUNT(*) AS efectivo FROM ventas v INNER JOIN metodo_de_pago m ON v.id_metodo_de_pago = m.id_metodo_de_pago WHERE m.nombre_metodo_de_pago = 'Efectivo'")->fetch_assoc()['efectivo'];
$Metodo_credito = $conn->query("SELECT COUNT(*) AS credito FROM ventas v INNER JOIN metodo_de_pago m ON v.id_metodo_de_pago = m.id_metodo_de_pago WHERE m.nombre_metodo_de_pago = 'Tarjeta de crédito'")->fetch_assoc()['credito'];
$Metodo_debito = $conn->query("SELECT COUNT(*) AS debito FROM ventas v INNER JOIN metodo_de_pago m ON v.id_metodo_de_pago = m.id_metodo_de_pago WHERE m.nombre_metodo_de_pago = 'Tarjeta de débito'")->fetch_assoc()['debito'];
$Metodo_transferencia = $conn->query("SELECT COUNT(*) AS transferencia FROM ventas v INNER JOIN metodo_de_pago m ON v.id_metodo_de_pago = m.id_metodo_de_pago WHERE m.nombre_metodo_de_pago = 'Transferencia'")->fetch_assoc()['transferencia'];

$Empleados_administrador = $conn->query("SELECT COUNT(*) AS administrador FROM empleados e INNER JOIN puesto p ON e.id_puesto = p.id_puesto WHERE p.nombre_del_puesto = 'Administrador del sistema'")->fetch_assoc()['administrador'];
$Empleados_compras = $conn->query("SELECT COUNT(*) AS compras FROM empleados e INNER JOIN puesto p ON e.id_puesto = p.id_puesto WHERE p.nombre_del_puesto = 'Encargado de compras'")->fetch_assoc()['compras'];
$Empleados_ventas = $conn->query("SELECT COUNT(*) AS ventas FROM empleados e INNER JOIN puesto p ON e.id_puesto = p.id_puesto WHERE p.nombre_del_puesto = 'Encargado de ventas'")->fetch_assoc()['ventas'];
$Empleados_inventario = $conn->query("SELECT COUNT(*) AS inventario FROM empleados e INNER JOIN puesto p ON e.id_puesto = p.id_puesto WHERE p.nombre_del_puesto = 'Encargado de inventario'")->fetch_assoc()['inventario'];
$Empleados_cobros = $conn->query("SELECT COUNT(*) AS cobros FROM empleados e INNER JOIN puesto p ON e.id_puesto = p.id_puesto WHERE p.nombre_del_puesto = 'Encargado de cobros'")->fetch_assoc()['cobros'];

$Ventas_por_año = $conn->query("
    SELECT 
        YEAR(fecha_venta) AS año, 
        COUNT(*) AS total_ventas 
    FROM ventas 
    WHERE fecha_venta >= '2025-01-01'
    GROUP BY año
    ORDER BY año ASC
");

$Años = [];
$Ventas_totales = [];

while ($row = $Ventas_por_año->fetch_assoc()) {
    $Años[] = (int) $row['año'];
    $Ventas_totales[] = (int) $row['total_ventas'];
}

/* -------------------------------------------------- */

$Deudores = $conn->query("
    SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo, deuda
    FROM clientes
    WHERE deuda > 0
    ORDER BY deuda DESC
    LIMIT 10
");
 
$Nombres_deudores = [];
$Deudas_deudores = [];
 
while ($row = $Deudores->fetch_assoc()) {
    $Nombres_deudores[] = $row['nombre_completo'];
    $Deudas_deudores[] = (float) $row['deuda'];
}

/* -------------------------------------------------- */

$Top_empleados_ventas = $conn->query("
    SELECT CONCAT(e.nombre, ' ', e.apellido) AS nombre_completo, COUNT(v.id_ventas) AS total_ventas
    FROM empleados e
    INNER JOIN ventas v ON e.id_empleados = v.id_empleado
    GROUP BY e.id_empleados
    ORDER BY total_ventas DESC
    LIMIT 5
");

$Nombres_empleados_ventas = [];
$Totales_empleados_ventas = [];

while ($row = $Top_empleados_ventas->fetch_assoc()) {
    $Nombres_empleados_ventas[] = $row['nombre_completo'];
    $Totales_empleados_ventas[] = (int) $row['total_ventas'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de estadísticas</title>
    <link rel="stylesheet" href="estadisticas.css">
</head>
<body>

    <div class="app-contenedor">
        <header class="barra-de-navegacion">
            <div class="logo-contenedor">
                <div class="logo"></div>
                <span class="logo-texto">Verdutech</span>
            </div>
            <a href="inicio.php" class="btn-salir"><img src="img/Volver.png" class="icono-volver">Volver al inicio</a>
        </header>

        <main class="contenedor-principal">
            <div class="contenedor-principal-grupo">

                <div class="titulo">Estadísticas</div>

                <div class="formulario-contenedor">
                    <div class="formulario-input">
                        <label>Tipo de estadísticas <span class="requerido">*</span></label>
                        <select id="filtrarSelect" class="input-estilo">
                            <option value="todos">Todos</option>
                            <option value="compras">Compras</option>
                            <option value="ventas">Ventas</option>
                            <option value="clientes">Clientes</option>
                            <option value="empleados">Empleados</option>
                            <option value="proveedores">Proveedores</option>
                            <option value="productos">Productos</option>
                        </select>
                    </div>
                    <button type="submit" onclick="filtrarGraficos()" class="btn-action"><img src="img/Buscar.png" class="iconos-principales">Buscar</button>
                    <a href="fpdf/ReporteEstadisticas.php" target="_blank" style="text-decoration: none" class="btn-action"><img src="img/Imprimir.png" class="iconos-principales">Imprimir</a>
                </div>

                <div class="display-grid">
                    <div class="botones">
                        <div class="numero"><?php echo $Total_clientes; ?></div>
                        <span>Total de clientes</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_empleados; ?></div>
                        <span>Total de empleados</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_proveedores; ?></div>
                        <span>Total de proveedores</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_ventas; ?></div>
                        <span>Total de ventas</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_compras; ?></div>
                        <span>Total de compras</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_productos; ?></div>
                        <span>Total de productos</span>
                    </div>
                </div>

                <div class="chart-contenedor">
                    <div class="chart-item" data-categoria="ventas">
                        <canvas id="myChartPie"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="ventas">
                        <canvas id="myChartBar"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="empleados">
                        <canvas id="myChartPie1"></canvas>
                        <canvas id="myChartPolar"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="ventas">
                        <canvas id="myChartLine"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="compras">
                        <canvas id="myChart2"></canvas>
                        <canvas id="myChartBar2"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="clientes">
                        <canvas id="myChartBar1"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="proveedores">
                        <canvas id="myChart5"></canvas>
                    </div>
                    <div class="chart-item" data-categoria="productos">
                        <canvas id="myChart6"></canvas>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script>

                    const ctxPie = document.getElementById('myChartPie');
                    const MetodoEfectivo = <?php echo $Metodo_efectivo; ?>;
                    const MetodoCredito = <?php echo $Metodo_credito; ?>;
                    const MetodoDebito = <?php echo $Metodo_debito; ?>;
                    const MetodoTransferencia = <?php echo $Metodo_transferencia; ?>;

                    new Chart(ctxPie, {
                        type: 'pie',
                        data: {
                            labels: ['Pago con efectivo', 'Pago con tarjeta de crédito', 'Pago con tarjeta de débito', 'Pago con transferencia'],
                            datasets: [{
                                label: 'Cantidad',
                                data: [MetodoEfectivo, MetodoCredito, MetodoDebito, MetodoTransferencia],

                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(255, 159, 64, 0.2)',
                                    'rgba(255, 205, 86, 0.2)',
                                    'rgba(54, 162, 235, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(54, 162, 235, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Los métodos de pago mas utilizados',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                </script>
                <script>

                    const ctxBar = document.getElementById('myChartBar');
                    const VentasCobradas = <?php echo $Ventas_cobradas; ?>;
                    const VentasPendientes = <?php echo $Ventas_pendientes; ?>;
                    const VentasCanceladas = <?php echo $Ventas_canceladas; ?>;

                    new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: ['Cobrada', 'Pendiente', 'Cancelada'],
                            datasets: [{
                                data: [VentasCobradas, VentasPendientes, VentasCanceladas],

                                backgroundColor: [
                                    'rgba(153, 102, 255, 0.2)',
                                    'rgba(75, 192, 192, 0.2)',
                                    'rgba(54, 162, 235, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(54, 162, 235, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Las ventas según su estado',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Ventas'
                                    }
                                }
                            }
                        }
                    });
                </script>
                <script>

                    const ctxPie1 = document.getElementById('myChartPie1');
                    const EmpleadosAdministrador = <?php echo $Empleados_administrador; ?>;
                    const EmpleadosCompras = <?php echo $Empleados_compras; ?>;
                    const EmpleadosVentas = <?php echo $Empleados_ventas; ?>;
                    const EmpleadosInventario = <?php echo $Empleados_inventario; ?>;
                    const EmpleadosCobros = <?php echo $Empleados_cobros; ?>;

                    new Chart(ctxPie1, {
                        type: 'pie',
                        data: {
                            labels: ['Administrador del sistema', 'Encargado de compras', 'Encargado de ventas', 'Encargado de inventario', 'Encargado de cobros'],
                            datasets: [{
                                label: 'Cantidad',
                                data: [EmpleadosAdministrador, EmpleadosCompras, EmpleadosVentas, EmpleadosInventario, EmpleadosCobros],

                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(255, 159, 64, 0.2)',
                                    'rgba(255, 205, 86, 0.2)',
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(153, 102, 255, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(153, 102, 255, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'La cantidad de empleados por puesto',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    position: 'top',
                                }
                            }
                        }
                    });
                </script>
                <script>
                    const ctxBar1 = document.getElementById('myChartBar1');
    
                    const NombresDeudores = <?php echo json_encode($Nombres_deudores); ?>;
                    const DeudasDeudores = <?php echo json_encode($Deudas_deudores); ?>;

                    new Chart(ctxBar1, {
                        type: 'bar',
                        data: {
                            labels: NombresDeudores,
                            datasets: [{
                                label: 'Deuda a cobrar',
                                data: DeudasDeudores,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(255, 159, 64, 0.2)',
                                    'rgba(255, 205, 86, 0.2)',
                                    'rgba(153, 102, 255, 0.2)',
                                    'rgba(75, 192, 192, 0.2)',
                                    'rgba(54, 162, 235, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(153, 102, 255, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(54, 162, 235, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Los 10 clientes con mayor deuda',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Deudas en ($)'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Clientes'
                                    }
                                }
                            }
                        }
                    });
                </script>
                <script>

                    const ctxLine = document.getElementById('myChartLine');
                    const Años = <?php echo json_encode($Años); ?>;
                    const VentasTotales = <?php echo json_encode($Ventas_totales); ?>;

                    new Chart(ctxLine, {
                        type: 'line',
                        data: {
                            labels: Años,
                            datasets: [{
                                data: VentasTotales,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderWidth: 1.5,
                                fill: true,
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Las ventas realizadas por año',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Ventas'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Años'
                                    }
                                }
                            }
                        }
                    });
                </script>
                <script>
                    const ctxPolar = document.getElementById('myChartPolar');
                    const NombresEmpleadosVentas = <?php echo json_encode($Nombres_empleados_ventas); ?>;
                    const TotalesEmpleadosVentas = <?php echo json_encode($Totales_empleados_ventas); ?>;

                    new Chart(ctxPolar, {
                        type: 'polarArea',
                        data: {
                            labels: NombresEmpleadosVentas,
                            datasets: [{
                                label: 'Ventas realizadas',
                                data: TotalesEmpleadosVentas,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(255, 159, 64, 0.2)',
                                    'rgba(255, 205, 86, 0.2)',
                                    'rgba(54, 162, 235, 0.2)',
                                    'rgba(75, 192, 192, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(255, 205, 86, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(75, 192, 192, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Los 5 empleados con mayor cantidad de ventas',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    position: 'top'
                                }
                            }
                        }
                    });
                </script>
                <script>
                    const ctx2 = document.getElementById('myChart2');

                    new Chart(ctx2, {
                        type: 'bar',
                        data: {
                            labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
                            datasets: [{
                                label: '# of Votes',
                                data: [12, 19, 3, 5, 2, 3],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
                <script>

                    const ctxBar2 = document.getElementById('myChartBar2');
                    const ComprasPagadas = <?php echo $Compras_pagadas; ?>;
                    const ComprasPendientes = <?php echo $Compras_pendientes; ?>;
                    const ComprasCanceladas = <?php echo $Compras_canceladas; ?>;

                    new Chart(ctxBar2, {
                        type: 'bar',
                        data: {
                            labels: ['Pagada', 'Pendiente', 'Cancelada'],
                            datasets: [{
                                data: [ComprasPagadas, ComprasPendientes, ComprasCanceladas],

                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.2)',
                                    'rgba(255, 159, 64, 0.2)',
                                    'rgba(255, 205, 86, 0.2)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(255, 159, 64, 1)',
                                    'rgba(255, 205, 86, 1)'
                                ],
                                borderWidth: 1.5
                            }]
                        },
                        options: {
                            devicePixelRatio: 3,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Las compras según su estado',
                                    font: {
                                        size: 15,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Compras'
                                    }
                                }
                            }
                        }
                    });
                </script>
                <script>
                    const ctx5 = document.getElementById('myChart5');

                    new Chart(ctx5, {
                        type: 'bar',
                        data: {
                            labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
                            datasets: [{
                                label: '# of Votes',
                                data: [12, 19, 3, 5, 2, 3],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
                <script>
                    const ctx6 = document.getElementById('myChart6');

                    new Chart(ctx6, {
                        type: 'bar',
                        data: {
                            labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
                            datasets: [{
                                label: '# of Votes',
                                data: [12, 19, 3, 5, 2, 3],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>
            </div>
        </main>
    </div>
    <script>
        function filtrarGraficos() {
            const filtrar = document.getElementById('filtrarSelect').value;
            const graficos = document.querySelectorAll('.chart-item');

            graficos.forEach(grafico => {
                const coincide = filtrar === 'todos' || grafico.dataset.categoria === filtrar;
                grafico.style.display = coincide ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>