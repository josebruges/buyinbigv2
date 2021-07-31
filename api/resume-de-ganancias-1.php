<link rel="stylesheet" href="js/chart/dist/Chart.css">
<link rel="stylesheet" href="js/chart/dist/Chart.min.css">
<script src="js/chart/dist/Chart.bundle.js"></script>
<script src="js/chart/dist/Chart.bundle.min.js"></script>
<script src="js/chart/dist/Chart.min.js"></script>
<script src="js/chart/dist/Chart.js"></script>

<div class="row">
  <div class="col-12 titulo-resume-ganancias">
    <h2>RESUMEN DE GANANCIAS</h2>
  </div>
</div>

<div class="row">
  <div class="col-12">

    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active resume-tab" id="pills-Actual-tab" data-toggle="pill" href="#pills-Actual" role="tab"
          aria-controls="pills-Actual" aria-selected=" true">Actual</a>
      </li>
      <li class="nav-item">
        <a class="nav-link resume-tab" id="pills-Ultimo-mes-tab" data-toggle="pill" href="#pills-Ultimo-mes" role="tab"
          aria-controls="pills-Ultimo-mes" aria-selected="false">Último mes</a>
      </li>
      <li class="nav-item">
        <a class="nav-link resume-tab" id="pills-Ultimo-ano-tab" data-toggle="pill" href="#pills-Ultimo-ano" role="tab"
          aria-controls="pills-Ultimo-ano" aria-selected="false">Último año</a>
      </li>

    </ul>
    <div class="tab-content" id="pills-tabContent">
      <div class="tab-pane fade show active" id="pills-Actual" role="tabpanel" aria-labelledby="pills-Earnings-tab">
        <div class="row">
          <div class="col-12">
            <div class="display-span">Fuente de pago
              <select class="btn btn-primary btn-fecha-bono dropdown-toggle" onchange="filtroTipo1(this.value)">
                <option value="">Selecciona</option>
                <option value="puntajeBinario">puntajeBinario</option>
                <option value="ventaDirecta">ventaDirecta</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="table-responsive">

              <table class="table table-hover my-5">
                <thead class="thead-inverse">
                  <tr>
                    <th style="border-top: none;">Fuente de pago</th>
                    <th style="border-top: none;">Miembro asociado</th>
                    <th style="border-top: none;">Cantidad</th>
                    <th style="border-top: none;">Fecha de la transacción</th>
                  </tr>
                </thead>
                <tbody id="cuerpo1">

                </tbody>
              </table>
            </div>

          </div>
        </div>
        <div class="row">
          <div class="col-12 displa-flex-justif">
            <div class="">
              <a name="" id="" class="btn btn-primary btn-buscar anterior-desaparecer" href="#" role="button">
                Anterior
              </a>
            </div>
            <div id="paginacion1" style="display:flex">
              <div class="redondo">1</div>
              <div class="no-redondo">2</div>
            </div>
            <div class="">
              <a name="" id="siguiente1" class="btn btn-primary btn-buscar" role="button">
                Siguiente
              </a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12 leyenda">
            <span class="span-style">

              <div class="redondo-rojo"></div>
              <p>Bono binario</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-oscuro"></div>

              <p>Venta directa</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-claro"></div>
              <p>Referidos tienda</p>

            </span>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-8 px-0 pr-0 pr-lg-2">
            <canvas style="background-color:white" id="bar-chart-grouped1" width="800" height="450"></canvas>
          </div>
          <div class="col-lg-4 my-3 my-lg-0 px-0 pl-0 pl-lg-0">
            <div class="col-12 mb-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart1" width="800" height="650"></canvas>
            </div>

            <div class="col-12 mt-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart21" width="800" height="650"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="pills-Ultimo-mes" role="tabpanel" aria-labelledby="pills-Ultimo-mes-tab">
        <div class="row">
          <div class="col-12">
            <div class="display-span">Fuente de pago
              <select class="btn btn-primary btn-fecha-bono dropdown-toggle" onchange="filtroTipo2(this.value)">
                <option value="">Selecciona</option>
                <option value="puntajeBinario">puntajeBinario</option>
                <option value="ventaDirecta">ventaDirecta</option>
              </select>
              Seleccionar fecha
              <input type="date" class="btn-fecha-bono-2" name="" id="data-reporte-1">
              <input type="date" class="btn-fecha-bono-2" name="" id="data-reporte-2">

              <a name="" id="" class="btn btn-primary btn-buscar"
                onclick="filtroFecha2('data-reporte-1', 'data-reporte-2')" role="button">
                BUSCAR
              </a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="table-responsive">
              <table class="table table-hover my-5">
                <thead class="thead-inverse">
                  <tr>
                    <th style="border-top: none;">Fuente de pago</th>
                    <th style="border-top: none;">Miembro asociado</th>
                    <th style="border-top: none;">Cantidad</th>
                    <th style="border-top: none;">Fecha de la transacción</th>
                  </tr>
                </thead>
                <tbody id="cuerpo2">

                </tbody>
              </table>
            </div>

          </div>
        </div>
        <div class="row">
          <div class="col-12 displa-flex-justif">
            <div class="">
              <a name="" id="" class="btn btn-primary btn-buscar anterior-desaparecer" href="#" role="button">
                Anterior
              </a>
            </div>
            <div id="paginacion2" style="display:flex">
              <div class="redondo">1</div>
              <div class="no-redondo">2</div>
            </div>
            <div class="">
              <a name="" id="siguiente2" class="btn btn-primary btn-buscar" role="button">
                Siguiente
              </a>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 leyenda">
            <span class="span-style">

              <div class="redondo-rojo"></div>
              <p>Bono binario</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-oscuro"></div>

              <p>Venta directa</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-claro"></div>
              <p>Referidos tienda</p>

            </span>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-8 px-0 pr-0 pr-lg-2">
            <canvas style="background-color:white" id="bar-chart-grouped2" width="800" height="450"></canvas>
          </div>
          <div class="col-lg-4 my-3 my-lg-0 px-0 pl-0 pl-lg-0">
            <div class="col-12 mb-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart2" width="800" height="650"></canvas>
            </div>

            <div class="col-12 mt-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart22" width="800" height="650"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="pills-Ultimo-ano" role="tabpanel" aria-labelledby="pills-Ultimo-ano-tab">
        <div class="row">
          <div class="col-12">
            <div class="display-span">Fuente de pago
              <select class="btn btn-primary btn-fecha-bono dropdown-toggle" onchange="filtroTipo3(this.value)">
                <option value="">Selecciona</option>
                <option value="puntajeBinario">puntajeBinario</option>
                <option value="ventaDirecta">ventaDirecta</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="table-responsive">

              <table class="table table-hover my-5">
                <thead class="thead-inverse">
                  <tr>
                    <th style="border-top: none;">Fuente de pago</th>
                    <th style="border-top: none;">Miembro asociado</th>
                    <th style="border-top: none;">Cantidad</th>
                    <th style="border-top: none;">Fecha de la transacción</th>
                  </tr>
                </thead>
                <tbody id="cuerpo3">

                </tbody>
              </table>
            </div>

          </div>
        </div>
        <div class="row">
          <div class="col-12 displa-flex-justif">
            <div class="">
              <a name="" id="" class="btn btn-primary btn-buscar anterior-desaparecer" href="#" role="button">
                Anterior
              </a>
            </div>
            <div id="paginacion3" style="display:flex">
              <div class="redondo">1</div>
              <div class="no-redondo">2</div>
            </div>
            <div class="">
              <a name="" id="siguiente3" class="btn btn-primary btn-buscar" role="button">
                Siguiente
              </a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12 leyenda">
            <span class="span-style">

              <div class="redondo-rojo"></div>
              <p>Bono binario</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-oscuro"></div>

              <p>Venta directa</p>
            </span>
            <span class="span-style">
              <div class="redondo-azul-claro"></div>
              <p>Referidos tienda</p>

            </span>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-8 px-0 pr-0 pr-lg-2">
            <canvas style="background-color:white" id="bar-chart-grouped3" width="800" height="450"></canvas>
          </div>
          <div class="col-lg-4 my-3 my-lg-0 px-0 pl-0 pl-lg-0">
            <div class="col-12 mb-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart3" width="800" height="650"></canvas>
            </div>

            <div class="col-12 mt-1 px-0">
              <canvas style="background-color: white! important;" id="doughnut-chart23" width="800" height="650"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!--<script src="js/pagination.min.js"></script>-->
<script>
  var montoVentaDir = 0;
  var montoPuntajeBin = 0;
  var montoGrafico1 = 0;
  var montoGrafico2 = 0;
  console.log("Estoy acá:");
  var array1 = [];
  var arrayP1 = [];
  var array2 = [];
  var arrayP2 = [];
  var auxArray3 = [];
  var arrayP3 = [];
  var auxArray = [];
  var arrayPagi1 = [];
  var arrayPagi2 = [];
  var arrayPagi3 = [];
  var arrayMesesVenta = [];
  var arrayMesesPunto = [];
  var arrayDiaVenta = [];
  var arrayDiaPunto = [];
  for (let x = 0; x < 12; x++) {
    arrayMesesVenta.push({meses: (x + 1), valor: 0});
    arrayMesesPunto.push({meses: (x + 1), valor: 0});
  }
  for (let x = 0; x < 31; x++) {
    arrayDiaVenta.push({dia: (x + 1), valor: 0});
    arrayDiaPunto.push({dia: (x + 1), valor: 0});
  }
  arrayMes = [
    {
      nombre: 'Enero',
      codigo: 0
    },
    {
      nombre: 'Febrero',
      codigo: 1
    },
    {
      nombre: 'Marzo',
      codigo: 2
    },
    {
      nombre: 'Abril',
      codigo: 3
    },
    {
      nombre: 'Mayo',
      codigo: 4
    },
    {
      nombre: 'Junio',
      codigo: 5
    },
    {
      nombre: 'Julio',
      codigo: 6
    },
    {
      nombre: 'Agosto',
      codigo: 7
    },
    {
      nombre: 'Septiembre',
      codigo: 8
    },
    {
      nombre: 'Octubre',
      codigo: 9
    },
    {
      nombre: 'Noviembre',
      codigo: 10
    },
    {
      nombre: 'Diciembre',
      codigo: 11
    }
  ];
  /* console.log(arrayMes); */
  var paginacion1 = {
    inicio: 0,
    fin: 0,
    pagina: 10,
    total: 0
  };
  var paginacion2 = {
    inicio: 0,
    fin: 0,
    pagina: 10,
    total: 0
  };
  var paginacion3 = {
    inicio: 0,
    fin: 0,
    pagina: 10,
    total: 0
  };
  var indice1 = 0;
  var indice2 = 0;
  var indice3 = 0;
  $(".backgroundblack").show();
  var user = JSON.parse(localStorage.getItem("dataUser"));
  $.ajax({
    type: 'POST',
    url: ApiURL + '/users/ganacias.php',
    //data:data,            
    dataType: 'json',
    data: { user: user.id },
    success: function (data) {
      var array = [];
      var fechaActual = new Date();
      array = data["data"];
      var arrayActual = array.filter(f => {
        var fecha = new Date(f.fecha_pago);
        return fechaActual.getMonth() === fecha.getMonth() && fechaActual.getFullYear() === fecha.getFullYear();
      });
      array1 = arrayActual;
      arrayP1 = arrayActual;
      console.log(arrayActual);
      var resulA = arrayActual.reduce((prev, current, indice) => {
        let exists = prev.find(x => x.tipo === current.tipo);
        if (!exists) {
          exists = {tipo: current.tipo, valor: 0, valorM: 0};
          exists.valor = 1;
          exists.valorM = parseFloat(current.monto);
          prev.push(exists);
        } else {
          var post = prev.findIndex(x => x.tipo === current.tipo);
          prev[post].valor += 1;
          prev[post].valorM += parseFloat(current.monto);
        }
        return prev;
      }, []);
      console.log(resulA);
      var resulBono = resulA.find(f => f.tipo === 'puntajeBinario');
      var resulVenta = resulA.find(f => f.tipo === 'ventaDirecta');
      var resulP = arrayMes.find(y => fechaActual.getMonth() === y.codigo);
      var arrayResultadoGa1 = [
        {
          tipo: 'ventaDirecta',
          acumulado: resulVenta ? resulVenta.valorM : 0,
          nombre: 'Venta directa',
          color: '#2C306B'
        },
        {
          tipo: 'puntajeBinario',
          acumulado: resulBono ? resulBono.valorM : 0,
          nombre: 'Bono binari',
          color: '#EA4262'
        },
        {
          tipo: 'refedio',
          acumulado: resulT ? resulT.referidoM : 0,
          nombre: 'Referidos',
          color: '#36e3f0'
        }
      ];
      arrayResultadoGa1.sort((a, b) => b.acumulado - a.acumulado);
      console.log(arrayResultadoGa1);
      new Chart(document.getElementById("bar-chart-grouped1"), {
        type: 'bar',
        data: {
          labels: [resulP.nombre],
          datasets: [
            {
              label: "Bono Binario",
              backgroundColor: "#EA4262",
              data: [(resulBono.valorM / arrayActual.length) * 100]
            }, {
              label: "Venta Directa",
              backgroundColor: "#2C306B",
              data: [(resulVenta.valorM / arrayActual.length) * 100]
            }, {
              label: "Referidos Tienda",
              backgroundColor: "#36e3f0",
              data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'Ingresos (%)'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart1"), {
        type: 'polarArea',
        data: {
          labels: [arrayResultadoGa1[0].nombre, "Otros"],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: ["#ea4261", "#eaeaea"],
              data: [arrayResultadoGa1[0].acumulado, arrayResultadoGa1[1].acumulado + arrayResultadoGa1[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'FUENTE DE MAYOR GANANCIA'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart21"), {
        type: 'doughnut',
        data: {
          labels: [arrayResultadoGa1[0].nombre, arrayResultadoGa1[1].nombre, arrayResultadoGa1[2].nombre],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: [arrayResultadoGa1[0].color, arrayResultadoGa1[1].color, arrayResultadoGa1[2].color],
              data: [arrayResultadoGa1[0].acumulado, arrayResultadoGa1[1].acumulado, arrayResultadoGa1[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'GANANCIAS TOTALES'
          }
        }
      });
      $("#siguiente1").off("click");
      $("#siguiente1").on("click", (evt) => {
        evt.preventDefault();
        sumar1();
      });
      pagina1(arrayP1, 0);

      /* Mes */
      var arrayActualMes = array.filter(f => {
        var fecha = new Date(f.fecha_pago);
        return fechaActual.getFullYear() === fecha.getFullYear();
      });
      array2 = arrayActualMes;
      arrayP2 = arrayActualMes;
      var coutM = 0;
      var resulM = arrayActualMes.reduce((prev, current, indice) => {
        var fecha = new Date(current.fecha_pago);
        coutM = indice;
        let exists = prev.find(x => (parseInt(x.month) - 1) === fecha.getMonth() || (parseInt(x.month) - 1) === fecha.getMonth() - 1);
        if (!exists) {
          exists = {month: fecha.getMonth(), punto: 0, venta: 0, referido: 0, puntoM: 0, ventaM: 0, referidoM: 0};
          if (current.tipo === 'ventaDirecta'){
            exists.venta = 1;
            exists.ventaM += parseFloat(current.monto);
          } else if (current.tipo === 'puntajeBinario') {
            exists.punto = 1;
            exists.puntoM += parseFloat(current.monto);
          } else if (current.tipo === 'referido') {
            exists.referido = 1;
            exists.referidoM += parseFloat(current.monto);
          }
          prev.push(exists);
        } else {
          var post = prev.findIndex(x => x.month === fecha.getMonth());
          if (current.tipo === 'ventaDirecta') {
            prev[post].venta += 1;
            prev[post].ventaM += parseFloat(current.monto);
          } else if (current.tipo === 'puntajeBinario') {
            prev[post].punto += 1;
            prev[post].puntoM += parseFloat(current.monto);
          } else if (current.tipo === 'referido') {
            prev[post].referido += 1;
            prev[post].referidoM += parseFloat(current.monto);
          }
        }
        return prev;
      }, []);
      console.log(resulM);
      var arrayMonth = [];
      var arrayPunto = [];
      var arrayVenta = [];
      var arrayReferido = [];
      resulM.filter(f => {
        var resulP = arrayMes.find(y => f.month === y.codigo);
        arrayMonth.push(resulP.nombre);
        arrayPunto.push(((f.punto / coutM) * 100).toFixed(2));
        arrayVenta.push(((f.venta / coutM) * 100).toFixed(2));
        arrayReferido.push(((f.referido / coutM) * 100).toFixed(2));
      });
      var resulT = resulM.find(f => f.month === (fechaActual.getMonth() - 1));
      var arrayResultadoGa2 = [
        {
          tipo: 'ventaDirecta',
          acumulado: resulT ? resulT.ventaM : 0,
          nombre: 'Venta directa',
          color: '#2C306B'
        },
        {
          tipo: 'puntajeBinario',
          acumulado: resulT ? resulT.puntoM : 0,
          nombre: 'Bono binari',
          color: '#EA4262'
        },
        {
          tipo: 'refedio',
          acumulado: resulT ? resulT.referidoM : 0,
          nombre: 'Referidos',
          color: '#36e3f0'
        }
      ];
      arrayResultadoGa2.sort((a, b) => b.acumulado - a.acumulado);
      new Chart(document.getElementById("bar-chart-grouped2"), {
        type: 'bar',
        data: {
          labels: arrayMonth,
          datasets: [
            {
              label: "Bono Binario",
              backgroundColor: "#EA4262",
              data: arrayPunto
            },{
              label: "Venta Directa",
              backgroundColor: "#2C306B",
              data: arrayVenta
            },{
              label: "Referidos Tienda",
              backgroundColor: "#36e3f0",
              data: arrayReferido
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'Resultados por mes (%)'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart2"), {
        type: 'doughnut',
        data: {
          labels: [arrayResultadoGa2[0].nombre, "Otros"],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: ["#ea4261", "#eaeaea"],
              data: [arrayResultadoGa2[0].acumulado, arrayResultadoGa2[1].acumulado + arrayResultadoGa2[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'FUENTE DE MAYOR GANANCIA'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart22"), {
        type: 'doughnut',
        data: {
          labels: [arrayResultadoGa2[0].nombre, arrayResultadoGa2[1].nombre, arrayResultadoGa2[2].nombre],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: [arrayResultadoGa2[0].color, arrayResultadoGa2[1].color, arrayResultadoGa2[2].color],
              data: [arrayResultadoGa2[0].acumulado, arrayResultadoGa2[1].acumulado, arrayResultadoGa2[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'GANANCIAS TOTALES'
          }
        }
      });
      pagina2(arrayP2, 0);
      $("#siguiente2").off("click");
      $("#siguiente2").on("click", (evt) => {
        evt.preventDefault();
        sumar2();
      });

      /* Años */
      auxArray3 = array;
      arrayP3 = array;
      var resul = auxArray3.reduce((prev, current) => {
        var fecha = new Date(current.fecha_pago);
        let exists = prev.find(x => x.year === fecha.getFullYear());
        if (!exists) {
          exists = {year: fecha.getFullYear(), punto: 0, venta: 0, referido: 0, puntoM: 0, ventaM: 0, referidoM: 0};
          if (current.tipo === 'ventaDirecta'){
            exists.venta = 1;
            exists.ventaM += parseFloat(current.monto);
          } else if (current.tipo === 'puntajeBinario') {
            exists.punto = 1;
            exists.puntoM += parseFloat(current.monto);
          } else if (current.tipo === 'referido') {
            exists.referido = 1;
            exists.referidoM += parseFloat(current.monto);
          }
          prev.push(exists);
        } else {
          var post = prev.findIndex(x => x.year === fecha.getFullYear());
          if (current.tipo === 'ventaDirecta') {
            prev[post].venta += 1;
            prev[post].ventaM += parseFloat(current.monto);
          } else if (current.tipo === 'puntajeBinario') {
            prev[post].punto += 1;
            prev[post].puntoM += parseFloat(current.monto);
          } else if (current.tipo === 'referido') {
            prev[post].referido += 1;
            prev[post].referidoM += parseFloat(current.monto);
          }
        }
        return prev;
      }, []);
      var arrayYear = [];
      var arrayPunto = [];
      var arrayVenta = [];
      var arrayReferido = [];
      resul.filter(f => {
        arrayYear.push(f.year);
        arrayPunto.push(((f.punto / auxArray3.length) * 100).toFixed(2));
        arrayVenta.push(((f.venta / auxArray3.length) * 100).toFixed(2));
        arrayReferido.push(((f.referido / auxArray3.length) * 100).toFixed(2));
      });
      var resulT = resulM.find(f => f.year === (fechaActual.getFullYear() - 1));
      var arrayResultadoGa3 = [
        {
          tipo: 'ventaDirecta',
          acumulado: resulT ? resulT.ventaM : 0,
          nombre: 'Venta directa',
          color: '#2C306B'
        },
        {
          tipo: 'puntajeBinario',
          acumulado: resulT ? resulT.puntoM : 0,
          nombre: 'Bono binari',
          color: '#EA4262'
        },
        {
          tipo: 'refedio',
          acumulado: resulT ? resulT.referidoM : 0,
          nombre: 'Referidos',
          color: '#36e3f0'
        }
      ];
      arrayResultadoGa3.sort((a, b) => b.acumulado - a.acumulado);
      console.log(arrayResultadoGa3);
      new Chart(document.getElementById("bar-chart-grouped3"), {
        type: 'bar',
        data: {
          labels: arrayYear,
          datasets: [
            {
              label: "Bono Binario",
              backgroundColor: "#EA4262",
              data: arrayPunto
            }, {
              label: "Venta Directa",
              backgroundColor: "#2C306B",
              data: arrayVenta
            }, {
              label: "Referidos Tienda",
              backgroundColor: "#36e3f0",
              data: arrayReferido
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'Ingresos (%)'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart3"), {
        type: 'doughnut',
        data: {
          labels: [arrayResultadoGa3[0].nombre, "Otros"],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: ["#ea4261", "#eaeaea"],
              data: [arrayResultadoGa3[0].acumulado, arrayResultadoGa3[1].acumulado + arrayResultadoGa3[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'FUENTE DE MAYOR GANANCIA'
          }
        }
      });
      new Chart(document.getElementById("doughnut-chart23"), {
        type: 'doughnut',
        data: {
          labels: [arrayResultadoGa3[0].nombre, arrayResultadoGa3[1].nombre, arrayResultadoGa3[2].nombre],
          datasets: [
            {
              label: "Population (millions)",
              backgroundColor: [arrayResultadoGa3[0].color, arrayResultadoGa3[1].color, arrayResultadoGa3[2].color],
              data: [arrayResultadoGa3[0].acumulado, arrayResultadoGa3[1].acumulado, arrayResultadoGa3[2].acumulado]
            }
          ]
        },
        options: {
          title: {
            display: true,
            text: 'GANANCIAS TOTALES'
          }
        }
      });
      pagina3(arrayP3, 0);
      $("#siguiente3").off("click");
      $("#siguiente3").on("click", (evt) => {
        evt.preventDefault();
        sumar3();
      });
      $(".backgroundblack").hide();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $(".backgroundblack").hide();
      console.log("Este es el error:", errorThrown);
    }
  });

  function pagina1(array, id) {
    arrayPagi = [];
    paginacion1.inicio = 0;
    paginacion1.fin = paginacion1.pagina;
    paginacion1.total = Math.ceil(array.length / paginacion1.pagina);
    $('#paginacion1').empty();
    if (paginacion1.total > 0) {
      for (let index = 0; index < paginacion1.total; index++) {
        arrayPagi1.push({
          inicio: paginacion1.inicio,
          fin: paginacion1.fin,
        });
        paginacion1.inicio = paginacion1.fin;
        paginacion1.fin += paginacion1.pagina;
        $('#paginacion1').append('<div class="no-redondo pagi1" id="pagi1' + index + '" onclick="seleccionPagi1(' + index + ');">' + (index + 1) + '</div>');
      }
      /* console.log(arrayPagi); */
      seleccionPagi1(id);
    } else {
      $("#cuerpo1").empty();
      $("#cuerpo1").html(htmlTableNoRegistro(4));
      $("#siguiente1").hide('');
    }
  }

  function seleccionPagi1(id) {
    /* console.log(id); */
    $('.pagi1').removeClass('redondo')
    $('.pagi1').addClass('no-redondo');
    $('#pagi1' + id).removeClass('no-redondo');
    $('#pagi1' + id).addClass('redondo');
    indice1 = id;
    var resul = arrayPagi1[indice1];
    auxArray = arrayP1.slice(resul.inicio, resul.fin);
    recorrer1(auxArray);
  }

  function recorrer1(array) {
    var aux_array = [];
    array.filter(f => aux_array.push(htmlTabla(f)));
    $("#cuerpo1").html(aux_array);
  }

  function sumar1() {
    if (indice1 < arrayPagi1.length - 1) {
      indice1 += 1;
    }
    seleccionPagi1(indice1);
  }

  function pagina2(array, id) {
    arrayPagi = [];
    paginacion2.inicio = 0;
    paginacion2.fin = paginacion2.pagina;
    paginacion2.total = Math.ceil(array.length / paginacion2.pagina);
    $('#paginacion2').empty();
    if (paginacion2.total > 0) {
      for (let index = 0; index < paginacion2.total; index++) {
        arrayPagi2.push({
          inicio: paginacion2.inicio,
          fin: paginacion2.fin,
        });
        paginacion2.inicio = paginacion2.fin;
        paginacion2.fin += paginacion2.pagina;
        $('#paginacion2').append('<div class="no-redondo pagi2" id="pagi2' + index + '" onclick="seleccionPagi2(' + index + ');">' + (index + 1) + '</div>');
      }
      /* console.log(arrayPagi); */
      seleccionPagi2(id);
    } else {
      $("#cuerpo2").empty();
      $("#cuerpo2").html(htmlTableNoRegistro(4));
      $("#siguiente2").hide('');
    }
  }

  function seleccionPagi2(id) {
    /* console.log(id); */
    $('.pagi2').removeClass('redondo')
    $('.pagi2').addClass('no-redondo');
    $('#pagi2' + id).removeClass('no-redondo');
    $('#pagi2' + id).addClass('redondo');
    indice2 = id;
    var resul = arrayPagi2[indice1];
    auxArray = arrayP2.slice(resul.inicio, resul.fin);
    recorrer2(auxArray);
  }

  function recorrer2(array) {
    var aux_array = [];
    array.filter(f => aux_array.push(htmlTabla(f)));
    $("#cuerpo2").html(aux_array);
  }

  function sumar2() {
    if (indice2 < arrayPagi2.length - 1) {
      indice2 += 1;
    }
    seleccionPagi2(indice2);
  }

  function pagina3(array, id) {
    arrayPagi = [];
    paginacion3.inicio = 0;
    paginacion3.fin = paginacion3.pagina;
    paginacion3.total = Math.ceil(array.length / paginacion3.pagina);
    $('#paginacion3').empty();
    if (paginacion3.total > 0) {
      for (let index = 0; index < paginacion3.total; index++) {
        arrayPagi3.push({
          inicio: paginacion3.inicio,
          fin: paginacion3.fin,
        });
        paginacion3.inicio = paginacion3.fin;
        paginacion3.fin += paginacion3.pagina;
        $('#paginacion3').append('<div class="no-redondo pagi3" id="pagi3' + index + '" onclick="seleccionPagi3(' + index + ');">' + (index + 1) + '</div>');
      }
      /* console.log(arrayPagi); */
      seleccionPagi3(id);
    } else {
      $("#cuerpo3").empty();
      $("#cuerpo3").html(htmlTableNoRegistro(4));
      $("#siguiente3").hide('');
    }
  }

  function seleccionPagi3(id) {
    /* console.log(id); */
    $('.pagi3').removeClass('redondo')
    $('.pagi3').addClass('no-redondo');
    $('#pagi3' + id).removeClass('no-redondo');
    $('#pagi3' + id).addClass('redondo');
    indice3 = id;
    var resul = arrayPagi3[indice3];
    auxArray = arrayP3.slice(resul.inicio, resul.fin);
    recorrer3(auxArray);
  }

  function recorrer3(array) {
    var aux_array = [];
    array.filter(f => aux_array.push(htmlTabla(f)));
    $("#cuerpo3").html(aux_array);
  }

  function sumar3() {
    if (indice3 < arrayPagi3.length - 1) {
      indice3 += 1;
    }
    seleccionPagi3(indice3);
  }

  function htmlTabla(param) {
    var year = param.fecha_pago.split('-')[0];
    var month = param.fecha_pago.split('-')[1];
    var date = param.fecha_pago.split('-')[2].split(' ')[0];
    /* var time = param.fecha_pago.split('-')[3].trim() */;
    var fecha = date + '/' + month + '/' + year;
    return html = `
      <tr> 
        <td>`+ `<img loading="lazy" style="width: 19px; margin-right: 10px;" src="imagen/icono-e-wallet.png" alt="">` + capitalize(param.tipo) + `</td>
        <td>`+ param.usuarioPaga + `</td>
        <td>`+ formatNumberUsd(param.monto) + ` USD</td>
        <td>`+ fecha + `</td>
      </tr>
    `;
  }

  function filtroTipo1(valor) {
    if (valor != '') {
      arrayP1 = array1.filter(f => f.tipo.indexOf(valor) > -1);
    } else {
      arrayP1 = array1;
    }
    paginacion1.inicio = 0;
    pagina1(arrayP1, 0);
  }

  function filtroTipo2(valor) {
    if (valor != '') {
      arrayP2 = array2.filter(f => f.tipo.indexOf(valor) > -1);
    } else {
      arrayP2 = array2;
    }
    paginacion2.inicio = 0;
    pagina2(arrayP2, 0);
  }

  function filtroFecha2(desde, hasta) {
    var fecha1 = $('#' + desde).val();
    var fecha2 = $('#' + hasta).val();
    if (fecha1 != '' && fecha2 != '') {
      var fecha3 = new Date(fecha1).getTime();
      var fecha4 = new Date(fecha2).getTime();
      if (fecha3 > fecha4) {
        var aux = fecha3;
        fecha3 = fecha4;
        fecha4 = aux
        $('#' + desde).val(fecha2);
        $('#' + hasta).val(fecha1);
      }
      arrayP2 = array2.filter(f => {
        var fecha = new Date(f.fecha_pago);
        return fecha.getTime() >= fecha3 && fecha.getTime() <= fecha4;
      });
      /* console.log(arrayP); */
    } else {
      arrayP2 = array2;
    }
    paginacion2.inicio = 0;
    pagina2(arrayP2, 0);
  }

  function filtroTipo3(valor) {
    console.log(auxArray3);
    if (valor != '') {
      arrayP3 = auxArray3.filter(f => f.tipo.indexOf(valor) > -1);
    } else {
      arrayP3 = auxArray3;
    }
    paginacion3.inicio = 0;
    pagina3(arrayP3, 0);
  }

  function htmlTableNoRegistro(valor) {
    return html = `
      <tr>
        <td  colspan="${ valor}">
          <div class="contec-no-registro">
            <img loading="lazy" src="imagen/no-hay-datos-registrDOS.png" alt="">
            <p>No hay Registro</p>
          </div>
        </td>
      </tr>
    `;
  }
</script>