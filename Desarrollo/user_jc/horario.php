<?php
foreach (glob("../class/*.php") as $filename) {
   include_once($filename);
}
session_start();
if(isset($_SESSION['usuario']))
{
  $usuario = unserialize($_SESSION['usuario']);
  if(get_class($usuario) == 'administrador') {
    $usuario = new jefeDeCarrera($usuario->getNombre(),$usuario->getNombreUsuario(),$usuario->getRut(),$usuario->getTipo());
    $_SESSION['usuario'] = serialize($usuario);
  }

  if(isset($_POST['cambiarCarrera']) && $_POST['cambiarCarrera'] == 'CAMBIAR CARRERA') {
    $_SESSION['carrera'] = null;
    $_SESSION['codigoSemestre'] = null;
    header("Location: ../home.php");
    exit();
  }

  if($_SESSION['tipoUsuario'] == 1 || $_SESSION['tipoUsuario'] == 3)
  {
?>
<!DOCTYPE HTML>
<html>

<head>
  <title>colour_blue</title>
  <meta charset="utf-8" />
  <meta name="description" content="website description" />
  <meta name="keywords" content="website keywords, website keywords" />
  <meta http-equiv="content-type" content="text/html; charset=windows-1252" />
  <link rel="stylesheet" type="text/css" href="../style/style.css" title="style" />
  <link rel="stylesheet" type="text/css" href="../style/bsc.css" title="style" />
  <script type="text/javascript" src="../js/jquery-1.6.1.min.js"></script>
  <script type="text/javascript" src="../js/jquery.easyui.min.js"></script>
  <script type="text/javascript" src="../js/js.js"></script>

  <script>
		$(function(){
                        $('.bin td.drop').droppable({
                                onDragEnter:function(){
                                        $(this).addClass('over');
                                },
                                onDragLeave:function(){
                                        $(this).removeClass('over');
                                },
                                onDrop:function(e,source){
                                        $(this).removeClass('over');
                                        ans = noAsignarHorario($(source).attr('id'));
                                        if(ans == 1)
                                        {
                                          var b = $(source).addClass('assigned');
                                          $('.last').append(b);
                                          b.draggable({
                                                revert: true
                                          });
                                          document.getElementById("resp").innerHTML = '<span class="error">Horario de la clase borrado.</span>';
                                        }
                                        else if(ans == 2)
                                        {
                                          document.getElementById("resp").innerHTML = '<span class="error">No se puede borrar horario de la clase.</span>';
                                        }
                                }
                        });
			$('.up .item').draggable({
				revert:true
			});
                        $('.down .item').draggable({
                                revert:true
                        });
			$('.down td.drop').droppable({
				onDragEnter:function(){
					$(this).addClass('over');
				},
				onDragLeave:function(){
					$(this).removeClass('over');
				},
				onDrop:function(e,source){
					$(this).removeClass('over');
                                                var err1 = '<span class="error">*Horario ya pertenece a un ramo diferente.</span>';
                                                var err2 = '<span class="error">*Ya existe una clase de la misma sección en este horario.</span>';
                                                var err3 = '<span class="error">*Este horario tiene una clase de una sección obtenida por solicitud.</span>';
                                                var scc = '<span class="error">*Horario asignado.</span>';
                                                var err4 = '<span class="error">*Horario no asignado, intentelo más tarde.</span>';
                                                var resp;
                                                resp = asignarHorario($(source).attr('id'),$(this).attr('id'));
                                                if(resp == '-2')
                                                {
                                                  document.getElementById("resp").innerHTML=err1;
                                                }
                                                else if(resp == '-1')
                                                {
                                                  document.getElementById("resp").innerHTML=err2;
                                                }
                                                else if(resp == '0')
                                                {
                                                  document.getElementById("resp").innerHTML=err3;
                                                }
                                                else if(resp == '1')
                                                {
                                                  document.getElementById("resp").innerHTML=scc;
                                                  var c = $(source).addClass('assigned');
						  $(this).append(c);
						  c.draggable({
						       	revert:true
						  });
                                                }
                                                else if(resp == '2')
                                                {
                                                  document.getElementById("resp").innerHTML=err4;
                                                }
				}
			});
		});
	</script>

</head>

<body>
  <div id="main">
    <div id="header">
      <div id="logo">
        <div id="logo_text">
          <!-- class="logo_colour", allows you to change the colour of the text -->
          <h1><a href="index.php">Universidad<span class="logo_colour"> Andrés Bello</span></a></h1>
          <h2>Herramienta de programación de horarios.</h2>
        </div>
      </div>
      <div id="menubar">
        <ul id="menu">
          <!-- put class="selected" in the li tag for the selected page - to highlight which page you're on -->
          <li><a href="../home.php">Home</a></li>
          <?php
          if(($_SESSION['tipoUsuario'] == 1 || $_SESSION['tipoUsuario'] == 3) && (is_string($_SESSION['carrera']) == true)) {
            echo '<li><a href="ramos.php">Ramos</a></li>';
            echo '<li><a href="secciones.php">Secciones y Vacantes</a></li>';
            echo '<li class="selected"><a href="horario.php">Horario</a></li>';
            echo '<li><a href="solicitudes.php">Solicitudes</a></li>';
          }
          ?>
          <li><a href="../logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
    <div id="site_content">
      <div id="content">
        <!-- insert the page content here -->
        <h2>Horario</h2>
        <div>
        <?php
          $numeroSemestres = numeroSemestres($_SESSION['carrera']);
          echo '<table class="centerTable"><tr>';
          for($i = 0;$i<$numeroSemestres;$i++)
          {
            if(isset($_GET['numeroSemestre']) && $_GET['numeroSemestre'] == ($i+1))
              echo '<td class="dc"><a href="horario.php?numeroSemestre='.($i+1).'">'.($i+1).'</a></td>';
            else
              echo '<td><a href="horario.php?numeroSemestre='.($i+1).'">'.($i+1).'</a></td>';
          }
          echo '</tr></table>';

          echo '<div class="up">';
          if(isset($_GET['numeroSemestre']))
            verClasesSinHorarioSemestre($_SESSION['carrera'],$_SESSION['codigoSemestre'],$_GET['numeroSemestre']);
          elseif(isset($_POST['numeroSemestre']))
            verClasesSinHorarioSemestre($_SESSION['carrera'],$_SESSION['codigoSemestre'],$_POST['numeroSemestre']);
          else
            verClasesSinHorarioSemestre($_SESSION['carrera'],$_SESSION['codigoSemestre'],1);
          echo '</div>';

          echo '<div class="bin"><table><tr><td class="drop" style="border: 1px black solid;">Borrar el horario<br>de una clase</td></tr></table></div>';

          echo '<div id="resp"></div>';
 
          if(isset($_GET['numeroSemestre']) || (isset($_POST['submit']) && $_POST['submit'] == 'Cambiar' && isset($_POST['numeroSemestre'])))
          {
            if(isset($_GET['numeroSemestre'])) 
            {
              verHorario($_SESSION['carrera'],$_SESSION['codigoSemestre'],$_GET['numeroSemestre']);
              echo '</div>';
            }
            elseif(isset($_POST['submit']) && $_POST['submit'] == 'Cambiar' && isset($_POST['numeroSemestre']))
            {
              verHorario($_SESSION['carrera'],$_SESSION['codigoSemestre'],$_POST['numeroSemestre']);
              echo '</div>';
            }
          }
          else
          {
            verHorario($_SESSION['carrera'],$_SESSION['codigoSemestre'],1);
            echo '</div>';
          }   

          echo '<table class="centerTable"><tr>';
          for($i = 0;$i<$numeroSemestres;$i++)
          {
            if(isset($_GET['numeroSemestre']) && $_GET['numeroSemestre'] == ($i+1))
              echo '<td class="dc"><a href="horario.php?numeroSemestre='.($i+1).'">'.($i+1).'</a></td>';
            else
              echo '<td><a href="horario.php?numeroSemestre='.($i+1).'">'.($i+1).'</a></td>';
          }
          echo '</tr></table>';
        ?>
        <br><br>
      </div>
    </div>
    <div id="content_footer"></div>
    <div id="footer">
    <?php
      if(($_SESSION['tipoUsuario'] == 1 || $_SESSION['tipoUsuario'] == 3) && !is_null($_SESSION['carrera']) &&$_SESSION['nroCarrera'] > 1) {
        echo '<form method="post" name="cambiarCarrera" target="_self"><input type="submit" name="cambiarCarrera" value="CAMBIAR CARRERA" class="inp"></input></form>';
        $j = 1;
      }
      if($_SESSION['tipoUsuario'] == 2 || $_SESSION['tipoUsuario'] == 3) {
        if(isset($j) && $j == 1)
          echo ' / ';
        echo '<a href="../user_admin/admin.php">Modo administrador</a>';
      }
    ?>
    </div>
  </div>
  <!--<script type='text/javascript' src='../js/jquery.js'></script>-->
  <script type='text/javascript' src='../js/jquery.simplemodal.js'></script>
  <!--<script type='text/javascript' src='../js/horario.js'></script>-->
  <script type='text/javascript' src='../js/bsc.js'></script>
</body>
</html>
<?php
  }
  else
  {
    header("Location: index.php");
    exit();
  }
}
else
{
  header("Location: index.php");
  exit();
}
