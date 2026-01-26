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
$Total_ventas = $conn->query("SELECT COUNT(*) AS total_ventas FROM ventas")->fetch_assoc()['total_ventas'];

$Ventas_cobradas = $conn->query("SELECT COUNT(*) AS cobradas FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Cobrada'")->fetch_assoc()['cobradas'];
$Ventas_pendientes = $conn->query("SELECT COUNT(*) AS pendientes FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Pendiente'")->fetch_assoc()['pendientes'];
$Ventas_canceladas = $conn->query("SELECT COUNT(*) AS canceladas FROM ventas v INNER JOIN estado e ON v.id_estado = e.id_estado WHERE e.nombre_del_estado = 'Cancelada'")->fetch_assoc()['canceladas'];

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
    WHERE YEAR(fecha_venta) >= 2025
    GROUP BY YEAR(fecha_venta)
    ORDER BY año ASC
");

$Años = [];
$Ventas_totales = [];

while ($row = $Ventas_por_año->fetch_assoc()) {
    $Años[] = $row['año'];
    $Ventas_totales[] = $row['total_ventas'];
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
                        <div class="numero"><?php echo 0; ?></div>
                        <span>Total de proveedores</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo $Total_ventas; ?></div>
                        <span>Total de ventas</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo 0; ?></div>
                        <span>Total de compras</span>
                    </div>
                    <div class="botones">
                        <div class="numero"><?php echo 0; ?></div>
                        <span>Total de productos</span>
                    </div>
                </div>

                <div class="chart-contenedor">
                    <div class="chart-item">
                        <canvas id="myChartPie"></canvas>
                    </div>
                    <div class="chart-item">
                        <canvas id="myChartBar"></canvas>
                    </div>
                    <div class="chart-item">
                        <canvas id="myChartPie1"></canvas>
                        <canvas id="myChart1"></canvas>
                    </div>
                    <div class="chart-item">
                        <canvas id="myChartLine"></canvas>
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
                    const ctx1 = document.getElementById('myChart1');

                    new Chart(ctx1, {
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
                                borderWidth: 2,
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
                                        text: 'Año'
                                    }
                                }
                            }
                        }
                    });
                </script>
            </div>
        </main>
    </div>
</body>
</html>