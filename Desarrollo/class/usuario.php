<?php
include_once('db/connect.php');
include_once('db/funciones.php');

class usuario {
  public $nombre;
  public $nombreUsuario;
  public $rut;
  private $password;

  function __construct($nombreUsuario,$password) {
    $this->nombreUsuario = $nombreUsuario;
    $this->password = $password;
  }

  function __destruct() {
    unset($this->nombreUsuario);
    unset($this->password);
    unset($this);
  }

  public function ingresarAlSistema() {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $nombreUsuario = $this->getNombreUsuario();
    $pass = $this->getPassword();
    $sql = "SELECT u.RUT,u.Nombre,u.Id_Tipo
             FROM Usuario AS u
            WHERE u.Nombre_Usuario = '{$nombreUsuario}' AND u.Password = '{$pass}';";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($rut,$nombre,$tipo);
    if($res->fetch())
    {
      if($tipo == 1 || $tipo == 3) 
      {
        $jdc = new jefeDeCarrera($nombre,$nombreUsuario,$rut);
        $_SESSION['usuario'] = serialize($jdc);
        $mysqli2 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sql2 = "SELECT c.Codigo,c.Nombre_Carrera,c.Periodo
                  FROM Carrera AS c
                 WHERE c.NombreUsuario_JC = '{$nombreUsuario}';";
        $res2 = $mysqli2->prepare($sql2);
        $res2->execute();
        $res2->bind_result($codigo,$nombre,$periodo);
        $i = 0;
        while($res2->fetch()) 
        {
          $_SESSION['carrera'] = $codigo;
          $i++;
        }
        if($i == 0) {
          $_SESSION['carrera'] = -1;
          $_SESSION['codigoSemestre'] = null;
        }
        elseif($i == 1) {
          $semestre = obtenerSemestre($periodo);
          $_SESSION['codigoSemestre'] = $semestre;
        }
        elseif($i>1) {
          $_SESSION['carrera'] = null;
          $_SESSION['codigoSemestre'] = null;
        }
        $_SESSION['nroCarrera'] = $i;
        $res2->free_result();
        $_SESSION['tipoUsuario'] = $tipo;
        $login = true;
      }
      elseif($tipo == 2) 
      {
        $admin = new administrador($nombre,$this->getNombreUsuario(),$rut,$tipo);
        $_SESSION['usuario'] = serialize($admin);
        $_SESSION['tipoUsuario'] = $tipo;
        $login = true;       
      }
      elseif($tipo == 4)
      {
        $departamento = new departamento($nombre,$this->getNombreUsuario(),$rut);
        $_SESSION['usuario'] = serialize($departamento);
        $_SESSION['tipoUsuario'] = $tipo;
        $login = true;  
      }
	  
      elseif($tipo == 5)
      {
        $jdl = new JefeDeLaboratorio($nombre,$this->getNombreUsuario(),$rut);
        $_SESSION['usuario'] = serialize($jdl);
        $_SESSION['tipoUsuario'] = $tipo;
        $login = true;  
      }
	  
    }
    $res->free_result();
    if(!isset($login))
      $login = false;
    return $login;
  }

  public function getNombre() {
    return $this->nombre;
  }

  public function getNombreUsuario() {
    return $this->nombreUsuario;
  }

  public function getPassword() {
    return $this->password;
  }

  public function getRut() {
    return $this->rut;
  }

  public function cerrarSesion() {
    $_SESSION = array();
    $session_name = session_name();
    session_destroy();
  }
}

class administrador extends usuario {
  
  function __construct($nombre,$nombreUsuario,$rut) {
    $this->nombre = $nombre;
    $this->nombreUsuario = $nombreUsuario;
    $this->rut = $rut;
  }

  public function verCarreras() {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL select_carreras()";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($codigo,$nombre_carrera,$nombreUsuarioJC,$periodo,$numero,$nombreJC,$rutJC);
    $car = 0;
    echo '<table>';
    while($res->fetch())
    {
      if($car == 0){
        echo '<tr><td>Nombre Carrera</td><td>Código</td><td>Nombre Jefe Carrera</td><td>RUT Jefe Carrera</td><td>Periodo</td><td>#Sem/Trim</td><td>Malla</td><td>Eliminar</td></tr>';
        $car = 1;}
      if($nombreJC == 'No asignado'){
        $nombreJC = '<a id="'.$codigo.'" class="asigna" href="">Asignar Jefe Carrera</a>';
        $rutJC = '';}
      else
      {
        $nombreJC = $nombreJC.'<br><a id="'.$codigo.'" class="cambia" href="">Cambiar</a>';
      }
      if($periodo == 1) $periodo = 'Semestral'; else $periodo = 'Trimestral';
      echo '<tr><td>'.$nombre_carrera.'</td><td>'.$codigo.'</td><td>'.$nombreJC.'</td><td>'.$rutJC.'</td><td>'.$periodo.'</td><td class="mid">'.$numero.'</td><td class="mid"><a id="'.$codigo.'" class="verMalla" href="">Ver malla</td><td class="mid"><a href="">X</a></td></tr>';
    }
    if(!isset($codigo))
      echo '<tr><td>No hay carreras.</td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function agregarCarrera($codigo,$nombre,$periodo,$regimen,$semestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Carrera(Codigo,Nombre_Carrera,Periodo,Regimen,Numero) VALUES ('{$codigo}','{$nombre}','{$periodo}','{$regimen}','{$semestre}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Carrera agregada con éxito.';
    }
    else
    {
      $answer = '*Carrera ya existe.';
    }
    return $answer;
  }

  public function agregarJefeDeCarrera($rut,$nombre,$nusuario,$pass) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $pass = md5($pass);
    $sql = "CALL agregar_jefe_carrera('{$rut}','{$nombre}','{$nusuario}','{$pass}')";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Jefe de Carrera agregado con éxito.';
    }
    else
    {
      $answer = '*Jefe de carrera ya existe.';
    }
    return $answer;
  }
 
  public function eliminarJefeDeCarrera($nombreUsuario) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL eliminar_jdc('{$nombreUsuario}')";
    if(($mysqli->query($sql)) == true)
    {
      $msg = 'Jefe de carrera eliminado.';
      return $msg;
    }
    else
    {
      $msg = 'Jefe de carrera no eliminado.';
      return $msg;
    } 
  }

  public function agregarRamo($codigo,$nombre,$tipo,$periodo,$teo,$ayu,$lab,$tall,$cre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Ramo(Codigo,Nombre,Teoria,Tipo,Periodo,Ayudantia,Laboratorio,Taller,Creditos) VALUES('{$codigo}','{$nombre}','{$teo}','{$tipo}','{$periodo}','{$ayu}','{$lab}','{$tall}','{$cre}')";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Ramo agregado con éxito.';
    }
    else
    {
      $answer = '*Ramo ya existe.';
    }
    return $answer;
  }

  public function relacionarRamoConCarrera($codigoRamo,$codigoCarrera,$semestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT c.Numero
             FROM carrera AS c
            WHERE c.Codigo = '{$codigoCarrera}';";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($answer);
    $res->fetch();
    $res->free_result();
    if($semestre <= $answer && $semestre > 0)
    {
      $mysqli2 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
      $sql2 = "SELECT ctr.Codigo_Ramo 
                FROM carrera_tiene_ramos AS ctr 
               WHERE ctr.Codigo_Carrera = '{$codigoCarrera}' AND ctr.Codigo_Ramo = '{$codigoRamo}';";
      $res2 = $mysqli2->prepare($sql2);
      $res2->execute();
      $res2->bind_result($answer2);
      if($res2->fetch())
      {
        $resp = '*Ya existe la relación entre el ramo y la carrera.';
      }
      else
      {
        $mysqli3 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sql3 = "INSERT INTO carrera_tiene_ramos (Codigo_Carrera,Codigo_Ramo,Semestre) VALUES ('{$codigoCarrera}','{$codigoRamo}','{$semestre}');";
        if(($mysqli3->query($sql3)) == true)
        {
          $resp = '*Ramo agregado con éxito.';
        }
        else
        {
          $resp = '*Relación no realizada.';
        }
      }
      $res2->free_result();
    }
    else
    {
      $resp = '*Debe seleccionar un semestre o trimestre dentro del rango de la carrera (1-'.$answer.').';
    }
    return $resp;
  }

  public function comenzarSemestre($codigoSemestre,$anno,$semestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL comenzarSemestre('{$codigoSemestre}','{$semestre}','{$anno}',NOW())";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Semestre comenzado.';
    }
    else
    {
      $answer = '*Semestre no comenzado.';
    }
    return $answer;
  }

  public function cerrarSemestre($codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $array = array();
    $i = 0;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT Codigo FROM carrera WHERE periodo = 1;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($codigoCarrera);
    while($res->fetch())
    {
      $mysqli2 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
      $sql2 = "SELECT Codigo_Ramo FROM carrera_tiene_ramos WHERE Codigo_Carrera = '{$codigoCarrera}';";
      $res2 = $mysqli2->prepare($sql2);
      $res2->execute();
      $res2->bind_result($codigoRamo);
      while($res2->fetch())
      {
        $mysqli3 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sql3 = "SELECT Codigo_Ramo FROM ramos_impartidos WHERE Codigo_Carrera = '{$codigoCarrera}' AND Codigo_Semestre = '{$codigoSemestre}' AND Codigo_Ramo = '{$codigoRamo}';";
        $res3 = $mysqli3->prepare($sql3);
        $res3->execute();
        $res3->bind_result($codigoRamo2);
        if($res3->fetch())
        {
        }
        else
        {
          $array[$i] = '<span class="error">*La carrera '.$codigoCarrera.' no tiene impartido su ramo '.$codigoRamo.'.</span>';
          $i++;
        }  
        $res3->free_result();
      }
      $res2->free_result();
    }
    $res->free_result();

    if($i <= 0)
    {  
      $mysqli4 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
      $sql4 = "CALL cerrarTrimestre('{$codigoSemestre}',NOW())";
      if(($mysqli4->query($sql4)) == true)
      {
        $answer = '*Semestre cerrado.';
      }
      else
      {
        $answer = '*Semestre no cerrado.';
      }
      return $answer;
    }
    return $array;
  }

  public function abrirSemestreAnterior($codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL abrirSemestreAnterior('{$codigoSemestre}',NOW())";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Semestre abierto nuevamente.';
    }
    else
    {
      $answer = '*Semestre no se puede abrir.';
    }
    return $answer;
  }

  public function comenzarTrimestre($codigoTrimestre,$anno,$trimestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL comenzarTrimestre('{$codigoTrimestre}','{$trimestre}','{$anno}',NOW())";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Trimestre comenzado.';
    }
    else
    {
      $answer = '*Trimestre no comenzado.';
    }
    return $answer;
  }

  public function cerrarTrimestre($codigoTrimestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $array = array();
    $i = 0;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT Codigo FROM carrera WHERE periodo = 2;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($codigoCarrera);
    while($res->fetch())
    {
      $mysqli2 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
      $sql2 = "SELECT Codigo_Ramo FROM carrera_tiene_ramos WHERE Codigo_Carrera = '{$codigoCarrera}';";
      $res2 = $mysqli2->prepare($sql2);
      $res2->execute();
      $res2->bind_result($codigoRamo);
      while($res2->fetch())
      {
        $mysqli3 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sql3 = "SELECT Codigo_Ramo FROM ramos_impartidos WHERE Codigo_Carrera = '{$codigoCarrera}' AND Codigo_Semestre = '{$codigoTrimestre}' AND Codigo_Ramo = '{$codigoRamo}';";
        $res3 = $mysqli3->prepare($sql3);
        $res3->execute();
        $res3->bind_result($codigoRamo2);
        if($res3->fetch())
        {
        }
        else
        {
          $array[$i] = '<span class="error">*La carrera '.$codigoCarrera.' no tiene impartido su ramo '.$codigoRamo.'.</span>';
          $i++;
        }  
        $res3->free_result();
      }
      $res2->free_result();
    }
    $res->free_result();

    if($i <= 0)
    {  
      $mysqli4 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
      $sql4 = "CALL cerrarTrimestre('{$codigoTrimestre}',NOW())";
      if(($mysqli4->query($sql4)) == true)
      {
        $answer = '*Trimestre cerrado.';
      }
      else
      {
        $answer = '*Trimestre no cerrado.';
      }
      return $answer;
    }
    return $array;
  }

  public function abrirTrimestreAnterior($codigoTrimestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL abrirTrimestreAnterior('{$codigoTrimestre}',NOW())";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Trimestre abierto nuevamente.';
    }
    else
    {
      $answer = '*Trimestre no se puede abrir.';
    }
    return $answer;
  }

  public function agregarProfesor($rutProfesor,$nombreProfesor,$gradoProfesor) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Profesor(Rut_Profesor,Nombre,Profesor_Grado) VALUES ('{$rutProfesor}','{$nombreProfesor}','{$gradoProfesor}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Profesor agregado.';
    }
    else
    {
      $answer = '*Profesor no agregado.';
    }
    return $answer;
  }

  public function verProfesores() {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT p.Rut_Profesor,p.Nombre FROM Profesor AS p";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($rut,$nombre);
    echo '<table><tr><td>Rut</td><td>Nombre</td><td>Relacionar</td><td>Eliminar</td></tr>';
    $flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$rut.'</td><td>'.$nombre.'</td><td><a href="">Relacionar</a></td><td><a href="">Eliminar</a></td>';
    }
    if($flag == 0)
      echo '<tr><td>No hay profesores.</td><td></td><td></td></tr></table>';
    else
      echo '</table>';
    $res->free_result();
  }

}

class jefeDeCarrera extends usuario {

  function __construct($nombre,$nombreUsuario,$rut) {
    $this->nombre = $nombre;
    $this->nombreUsuario = $nombreUsuario;
    $this->rut = $rut;
  }

  function __destruct() {
    unset($this->nombre);
    unset($this->nombreUsuario);
    unset($this->rut);
    unset($this);
  }

  public function verMalla($codigoCarrera) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT ctr.Codigo_Ramo,r.Nombre,r.Tipo,ctr.Semestre
             FROM carrera_tiene_ramos AS ctr
             INNER JOIN Ramo AS r ON ctr.Codigo_Ramo = r.Codigo
            WHERE ctr.Codigo_Carrera = '{$codigoCarrera}' ORDER BY ctr.Semestre;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($codigoRamo,$nombreRamo,$tipo,$semestreRamo);
    $flag = 0;
    $periodo = obtenerPeriodoCarrera($codigoCarrera);
    if($periodo == 1)
      echo '<table><tr><td>Semestre</td><td>Código</td><td>Nombre</td></tr>';
    else
      echo '<table><tr><td>Trimestre</td><td>Código</td><td>Nombre</td></tr>';
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      $semestreRamo = anhoSemestre($periodo,$semestreRamo);
      echo '<tr><td>'.$semestreRamo.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No hay ramos asociados a la carrera.</td><td></td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function verRamosQuePiden($codigoCarrera,$codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT s.Id,s.Codigo_Ramo,r.Nombre,s.Carrera_Solicitante,s.Vacantes
             FROM Solicitud AS s
             INNER JOIN Ramo AS r ON r.Codigo = s.Codigo_Ramo
            WHERE s.Codigo_Semestre = '{$codigoSemestre}' AND s.Carrera = '{$codigoCarrera}' AND s.Estado = 1;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idSolicitud,$codigoRamo,$nombreRamo,$carreraSolicitante,$vacantes);
    $flag = 0;
    echo '<table><tr><td>#</td><td>Remitente</td><td>Código ramo</td><td>Nombre ramo</td><td>Vacantes</td></tr>';
    while($res->fetch())
    {
      $flag = 1;
      echo '<tr><td>'.$idSolicitud.'</td><td>'.$carreraSolicitante.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$vacantes.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No existen solicitudes.</td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function verRamosQuePido($codigoCarrera,$codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT s.Id,s.Codigo_Ramo,r.Nombre,s.Carrera,s.Vacantes
             FROM Solicitud AS s
             INNER JOIN Ramo AS r ON r.Codigo = s.Codigo_Ramo
            WHERE s.Codigo_Semestre = '{$codigoSemestre}' AND s.Carrera_Solicitante = '{$codigoCarrera}' AND s.Estado = 1;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idSolicitud,$codigoRamo,$nombreRamo,$carreraDestino,$vacantes);
    $flag = 0;
    echo '<table><tr><td>#</td><td>Destino</td><td>Código ramo</td><td>Nombre ramo</td><td>Vacantes</td></tr>';
    while($res->fetch())
    {
      $flag = 1;
      echo '<tr><td>'.$idSolicitud.'</td><td>'.$carreraDestino.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$vacantes.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No existen solicitudes.</td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function verProgramacionVsPresupuesto($codigoCarrera,$codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT p.presupuesto
             FROM Presupuesto AS p
            WHERE p.Codigo_Carrera = '{$codigoCarrera}' AND p.Codigo_Semestre = '{$codigoSemestre}'";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($presupuesto);
    $presupuesto2 = '';
    echo '<table>';
    if($res->fetch())
    {
      $presupuesto = '$ '.$presupuesto;
      echo '<tr><td>'.$presupuesto.'</td></tr>';
      echo '<tr><td><a class="ingresarPresupuesto" href="">Cambiar presupuesto del semestre.</a></td></tr>';
    }
    else
    {
      echo '<tr><td><a class="ingresarPresupuesto" href="">Ingresar presupuesto del semestre.</a></td></tr>';
    }
    echo '</table>';
    $res->free_result();
  }

  public function verProfesoresAsignados($codigoCarrera,$codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT DISTINCT p.RUT_Profesor,p.Nombre,p.Profesor_Grado
             FROM Profesor AS p
             INNER JOIN Seccion AS s ON s.Codigo_Carrera = '{$codigoCarrera}' AND s.Codigo_Semestre = '{$codigoSemestre}'
             INNER JOIN Clase AS c ON c.Seccion_Id = s.Id AND c.Codigo_Semestre = '{$codigoSemestre}' AND c.RUT_Profesor IS NOT NULL
             INNER JOIN Ramo AS r ON r.Codigo = s.Codigo_Ramo 
            WHERE p.RUT_Profesor = c.RUT_Profesor ORDER BY p.Nombre;";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($rut,$nombre,$grado);
    $flag = 0;
    echo '<table><tr><td>RUT</td><td>Nombre</td></tr>';
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$rut.'</td><td>'.$nombre.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No hay profesores asignados.</td><td></td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function verProfesoresSinCargaAcademica($codigoCarrera) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT p.Rut_Profesor, p.Nombre
             FROM Profesor AS p
            WHERE p.codigo_carrera = codigoCarrera AND p.Rut_Profesor NOT IN (SELECT s.Rut_Profesor FROM Seccion AS s WHERE s.Rut_Profesor IS NOT NULL);";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($rut,$nombre);
    $flag = 0;
    echo '<table><tr><td>RUT</td><td>Nombre</td></tr>';
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$rut.'</td><td>'.$nombre.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No hay profesores sin carga.</td><td></td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function verSeccionesSinProfesor($codigoCarrera,$codigoSemestre) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT DISTINCT s.Id,s.Numero_Seccion,r.Codigo,r.Nombre
             FROM Seccion AS s
             INNER JOIN Clase AS c ON c.Seccion_Id = s.Id AND c.Codigo_Semestre = '{$codigoSemestre}' AND c.RUT_Profesor IS NULL
             INNER JOIN Ramo AS r ON r.Codigo = s.Codigo_Ramo 
            WHERE s.Codigo_Carrera = '{$codigoCarrera}' AND s.Codigo_Semestre = '{$codigoSemestre}'";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idSeccion,$numeroSeccion,$codigoRamo,$nombreRamo);
    $flag = 0;
    echo '<table><tr><td>Sección</td><td>Nombre</td><td>Código</td></tr>';
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td><a href="user_jc/clases.php?codigoRamo='.$codigoRamo.'&mod=0&seccionId='.$idSeccion.'">'.$numeroSeccion.'</a></td><td>'.$nombreRamo.'</td><td>'.$codigoRamo.'</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No hay secciones sin profesor.</td><td></td></tr>';
    echo '</table>';
    $res->free_result();
  }

  public function ingresarPresupuesto($codigoCarrera,$codigoSemestre,$presupuesto)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Presupuesto(Codigo_Carrera,Codigo_Semestre,Presupuesto) VALUES('{$codigoCarrera}','{$codigoSemestre}','{$presupuesto}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Presupuesto ingresado.';
    }
    else
    {
      $answer = '*Presupuesto no ingresado.';
    }
    return $answer;
  }

  public function cambiarPresupuesto($codigoCarrera,$codigoSemestre,$presupuesto)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "UPDATE Presupuesto SET Presupuesto = '{$presupuesto}' WHERE Codigo_Carrera = '{$codigoCarrera}' AND Codigo_Semestre = '{$codigoSemestre}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Presupuesto actualizado.';
    }
    else
    {
      $answer = '*Presupuesto no actualizado.';
    }
    return $answer;
  }

  public function impartirRamo($codigoCarrera,$codigoRamo,$codigoSemestre,$primera) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    if($primera == 1)
      $sql = "INSERT INTO Ramos_Impartidos(Codigo_Carrera,Codigo_Ramo,Codigo_Semestre,Impartido) VALUES('{$codigoCarrera}','{$codigoRamo}','{$codigoSemestre}',1);";
    elseif($primera == 0)
      $sql = "UPDATE Ramos_Impartidos SET Impartido = 1 WHERE Codigo_Carrera = '{$codigoCarrera}' AND Codigo_Ramo = '{$codigoRamo}' AND Codigo_Semestre = '{$codigoSemestre}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Ramo impartido.';
    }
    else
    {
      $answer = '*Ramo no impartido.';
    }
    return $answer;
  }

  public function noImpartirRamo($codigoCarrera,$codigoRamo,$codigoSemestre,$primera) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    if($primera == 1)
      $sql = "INSERT INTO Ramos_Impartidos(Codigo_Carrera,Codigo_Ramo,Codigo_Semestre,Impartido) VALUES('{$codigoCarrera}','{$codigoRamo}','{$codigoSemestre}',2);";
    else
      $sql = "UPDATE Ramos_Impartidos SET Impartido = 2 WHERE Codigo_Carrera = '{$codigoCarrera}' AND Codigo_Ramo = '{$codigoRamo}' AND Codigo_Semestre = '{$codigoSemestre}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Ramo no impartido.';
    }
    else
    {
      $answer = '*Ramo no se puede no impartir.';
    }
    return $answer;
  }


  public function crearSeccion($codigoRamo,$codigoSemestre,$codigoCarrera) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT c.Regimen
             FROM Carrera AS c
            WHERE c.Codigo = '{$codigoCarrera}';";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($regimen);
    $res->fetch();
    $res->free_result();

    $mysqli2 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql2 = "SELECT MAX(s.Numero_Seccion)
              FROM Seccion AS s
              INNER JOIN Carrera AS c ON c.Codigo = s.Codigo_Carrera AND c.Regimen = '{$regimen}'
             WHERE s.Codigo_Ramo = '{$codigoRamo}' AND s.Codigo_Semestre = '{$codigoSemestre}';";
    $res2 = $mysqli2->prepare($sql2);
    $res2->execute();
    $res2->bind_result($numeroSeccion);
    $res2->fetch();
    $res2->free_result();

    $mysqli3 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql3 = "SELECT r.Teoria,r.Ayudantia,r.Laboratorio,r.Taller
              FROM Ramo AS r
             WHERE r.Codigo = '{$codigoRamo}';";
    $res3 = $mysqli3->prepare($sql3);
    $res3->execute();
    $res3->bind_result($teoria,$ayudantia,$laboratorio,$taller);
    $res3->fetch();
    $res3->free_result();

    $mysqli4 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    if($numeroSeccion == 0) {
      if($regimen == 'D')
        $numeroSeccion = 1;
      elseif($regimen == 'V')
        $numeroSeccion = 100;
    }
    else
      $numeroSeccion++;
    $sql4 = "INSERT INTO Seccion(Numero_Seccion,NRC,Codigo_Ramo,Codigo_Carrera,Codigo_Semestre,Regimen,Vacantes) VALUES('{$numeroSeccion}',1524,'{$codigoRamo}','{$codigoCarrera}','{$codigoSemestre}','{$regimen}',60);";
    if(($mysqli4->query($sql4)) == true)
    {
      $answer = '*Sección creada.';
    }
    else
    {
      $answer = '*Sección no creada.';
    }

    $mysqli5 = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql5 = "SELECT s.Id
              FROM Seccion AS s
             WHERE s.Numero_Seccion = '{$numeroSeccion}' AND s.Codigo_Ramo = '{$codigoRamo}' AND s.Codigo_Carrera = '{$codigoCarrera}' AND s.Codigo_Semestre = '{$codigoSemestre}';";
    $res5 = $mysqli5->prepare($sql5);
    $res5->execute();
    $res5->bind_result($idSeccion);
    $res5->fetch();
    $res5->free_result();

    $teoria = $teoria/2;
    if($teoria > 0) {
      for($i = 0;$i<$teoria;$i++)
      {
        $mysqliteo = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sqlteo = "INSERT INTO Clase(Clase_Tipo,Seccion_Id,Codigo_Semestre) VALUES('Teoria','{$idSeccion}','{$codigoSemestre}');";
        $mysqliteo->query($sqlteo);
      } 
    }
    $ayudantia = $ayudantia/2;
    if($ayudantia > 0) {
      for($i = 0;$i<$ayudantia;$i++)
      {
        $mysqliteo = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sqlteo = "INSERT INTO Clase(Clase_Tipo,Seccion_Id,Codigo_Semestre) VALUES('Ayudantia','{$idSeccion}','{$codigoSemestre}');";
        $mysqliteo->query($sqlteo);
      }
    }
    $laboratorio = $laboratorio/2;
    if($laboratorio > 0) {
      for($i = 0;$i<$laboratorio;$i++)
      {
        $mysqliteo = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sqlteo = "INSERT INTO Clase(Clase_Tipo,Seccion_Id,Codigo_Semestre) VALUES('Laboratorio','{$idSeccion}','{$codigoSemestre}');";
        $mysqliteo->query($sqlteo);
      }
    }
    $taller = $taller/2;
    if($taller > 0) {
      for($i = 0;$i<$taller;$i++)
      {
        $mysqliteo = @new mysqli($db_host, $db_user, $db_pass, $db_database);
        $sqlteo = "INSERT INTO Clase(Clase_Tipo,Seccion_Id,Codigo_Semestre) VALUES('Taller','{$idSeccion}','{$codigoSemestre}');";
        $mysqliteo->query($sqlteo);
      }
    }

    return $answer;
  }

  public function solicitarVacantes($codigoRamo,$codigoCarrera,$codigoCarreraSolicitante,$numeroVacantes,$codigoSemestre)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL solicitarVacantes('{$codigoRamo}','{$codigoCarrera}','{$codigoCarreraSolicitante}','{$numeroVacantes}','{$codigoSemestre}',NOW())";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Solicitud enviada.';
    }
    else
    {
      $answer = '*Solicitud no enviada.';
    }
    return $answer;
  }

  public function verSolicitudes($codigoCarrera,$codigoSemestre) {
    echo '<h4>Solicitudes pedidas a mi</h4>';
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL verSolicitudesOtros('{$codigoCarrera}','{$codigoSemestre}')";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idSolicitud,$codigoRamo,$nombreRamo,$carreraSolicitante,$vacantes,$vacantesAsignadas,$fecha_envio,$fechaRespuesta,$estado);
    echo '<table><tr><td class="dc">Esperando</td></tr>';
    echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera solicitante</td><td class="dc"># vacantes</td><td class="dc">Fecha envio</td><td class="dc">Estado</td><td class="dc">Responder</td></tr>';
    $flag = 0;
    $aceptadas = 0;
    $denegadas = 0;
    while($res->fetch())
    {
      if($flag == 0) {
        $flag = 1;}
      if($estado == 1)
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraSolicitante.'</td><td class="mid">'.$vacantes.'</td><td>'.$fecha_envio.'</td><td>Esperando</td><td><a id="'.$idSolicitud.'" class="responderSolicitud" href="">Responder</a></td></tr>';
      elseif($estado == 2)
      {
        if($aceptadas == 0)
        {
          $aceptadas = 1;
          echo '<tr></tr>';
          echo '<tr><td class="dc">Aceptadas</td></tr>';
          echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera solicitante</td><td class="dc">Vacantes pedidas</td><td class="dc">Vacantes asignadas</td><td class="dc">Fecha envio</td><td class="dc">Fecha respuesta</td><td class="dc">Estado</td></tr>';
        }
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraSolicitante.'</td><td class="mid">'.$vacantes.'</td><td class="mid">'.$vacantesAsignadas.'</td><td>'.$fecha_envio.'</td><td>'.$fechaRespuesta.'</td><td>Aceptada</td></tr>';
      }
      elseif($estado == 3)
      {
        if($denegadas == 0)
        {
          $denegadas = 1;
          echo '<tr></tr>';
          echo '<tr><td class="dc">Denegadas</td></tr>';
          echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera solicitante</td><td class="dc">Vacantes pedidas</td><td class="dc">Vacantes asignadas</td><td class="dc">Fecha envio</td><td class="dc">Fecha respuesta</td><td class="dc">Estado</td></tr>';
        }
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraSolicitante.'</td><td class="mid">'.$vacantes.'</td><td class="mid">'.$vacantesAsignadas.'</td><td>'.$fecha_envio.'</td><td>'.$fechaRespuesta.'</td><td>Denegada</td></tr>';
      }
    }
    if($flag == 0)
      echo 'No hay solicitudes</table>';
    else
      echo '</table>';
    $res->free_result();
  
    echo '<h4>Solicitudes realizadas por mi</h4>';
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL verSolicitudesMias('{$codigoCarrera}','{$codigoSemestre}')";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idSolicitud,$codigoRamo,$nombreRamo,$carreraDestinataria,$vacantes,$vacantesAsignadas,$fecha_envio,$fechaRespuesta,$estado);
    echo '<table><tr><td class="dc">Esperando</td></tr>';
    echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera destinataria</td><td class="dc"># vacantes</td><td class="dc">Fecha envio</td><td class="dc">Estado</td><td class="dc">Modificar</td><td class="dc">Eliminar</td></tr>';
    $flag = 0;
    $aceptadas = 0;
    $denegadas = 0;
    while($res->fetch())
    {
      if($flag == 0) {
        $flag = 1;}
      if($estado == 1)
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraDestinataria.'</td><td class="mid">'.$vacantes.'</td><td>'.$fecha_envio.'</td><td>Esperando</td><td><form method="post" name="modificarSolicitud" target="_self"><input type="text" name="numeroVacantes" value="'.$vacantes.'" class="xs"></input><input type="hidden" name="hiddenSolicitudId" value="'.$idSolicitud.'"></input> <input type="submit" name="submit" value="Modificar"></input></form></td><td><form method="post" name="eliminarSolicitud" target="_self"><input type="hidden" name="hiddenSolicitudId" value="'.$idSolicitud.'"></input><input type="submit" name="submit" value="Eliminar"></input></form></td></tr>';
      elseif($estado == 2)
      {
        if($aceptadas == 0)
        {
          $aceptadas = 1;
          echo '<tr></tr>';
          echo '<tr><td class="dc">Aceptadas</td></tr>';
          echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera solicitante</td><td class="dc">Vacantes pedidas</td><td class="dc">Vacantes asignadas</td><td class="dc">Fecha envio</td><td class="dc">Fecha respuesta</td><td class="dc">Estado</td></tr>';
        }
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraDestinataria.'</td><td class="mid">'.$vacantes.'</td><td class="mid">'.$vacantesAsignadas.'</td><td>'.$fecha_envio.'</td><td>'.$fechaRespuesta.'</td><td>Aceptada</td></tr>';
      }
      elseif($estado == 3)
      {
        if($denegadas == 0)
        {
          $denegadas = 1;
          echo '<tr></tr>';
          echo '<tr><td class="dc">Denegadas</td></tr>';
          echo '<tr><td class="dc">Id Solicitud</td><td class="dc">Código ramo</td><td class="dc">Nombre ramo</td><td class="dc">Carrera solicitante</td><td class="dc">Vacantes pedidas</td><td class="dc">Vacantes asignadas</td><td class="dc">Fecha envio</td><td class="dc">Fecha respuesta</td><td class="dc">Estado</td></tr>';
        }
        echo '<tr><td>'.$idSolicitud.'</td><td>'.$codigoRamo.'</td><td>'.$nombreRamo.'</td><td>'.$carreraDestinataria.'</td><td class="mid">'.$vacantes.'</td><td class="mid">'.$vacantesAsignadas.'</td><td>'.$fecha_envio.'</td><td>'.$fechaRespuesta.'</td><td>Denegada</td></tr>';
      }
    }
    if($flag == 0)
      echo 'No hay solicitudes</table>';
    else
      echo '</table>';
    $res->free_result();
  }

  public function revisarSolicitud($idSolicitud)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL revisarSolicitud('{$idSolicitud}')";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($id,$codigoRamo,$carrera,$carreraSolicitante,$vacantes,$codigoSemestre,$fecha_envio,$fecha_termino,$estado);
    $res->fetch();
    echo '<table><h4>Solicitud número '.$id.'</h4></table>';
    echo '<table><tr><td>Carrera solicitante: '.$carreraSolicitante.'</td></tr><tr><td>Número vacantes: '.$vacantes.'</td></tr></table>';
    $res->free_result();
    return $vacantes;
  }

  public function responderSolicitud($idSolicitud,$respuesta,$vacantes,$seccion)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    if($respuesta == 2)
      $sql = "UPDATE Solicitud SET estado = 2, Vacantes_Asignadas = '{$vacantes}', fecha_respuesta = NOW(), Seccion_Asignada = $seccion
              WHERE id = '{$idSolicitud}';";
    elseif($respuesta == 3)
      $sql = "UPDATE Solicitud SET estado = 3, vacantes_asignadas = 0, fecha_respuesta = NOW(), Seccion_Asignada = NULL 
              WHERE id = '{$idSolicitud}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Solicitud respondida.';
    }
    else
    {
      $answer = '*Solicitud no respondida.';
    }
    return $answer;
  }

  public function modificarSolicitud($idSolicitud,$numeroVacantes)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL modificarSolicitud('{$idSolicitud}','{$numeroVacantes}')";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Solicitud modificada.';
    }
    else
    {
      $answer = '*Solicitud no modificada.';
    }
    return $answer;
  }

  /*public function eliminarSolicitud($idSolicitud)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "CALL eliminarSolicitud('{$idSolicitud}')";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Solicitud eliminada.';
    }
    else
    {
      $answer = '*Solicitud no eliminada.';
    }
    return $answer;
  }*/

  public function asignarSeccion($idClase,$rutProfesor)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "UPDATE Clase SET RUT_Profesor = '{$rutProfesor}' WHERE Id = '{$idClase}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Profesor asignado.';
    }
    else
    {
      $answer = '*Profesor asignado.';
    }
    return $answer;
  }

  public function eliminarProfesorDeSeccion($idClase)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "UPDATE Clase SET RUT_Profesor = NULL WHERE Id = '{$idClase}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Profesor eliminado.';
    }
    else
    {
      $answer = '*Profesor no eliminado.';
    }
    return $answer;
  }

  public function asignarHorario($idClase,$dos,$tipo)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    if($tipo == 1)
    {
      $sql = "UPDATE Clase SET Dia = '{$dos}' WHERE Id = '{$idClase}';";
      if(($mysqli->query($sql)) == true)
      {
        $answer = '*Día asignado.';
      }
      else
      {
        $answer = '*Día no asignado.';
      }
      return $answer;
    }
    elseif($tipo == 2)
    {
      $sql = "UPDATE Clase SET Modulo_Inicio = '{$dos}' WHERE Id = '{$idClase}';";
      if(($mysqli->query($sql)) == true)
      {
        $answer = '*Módulo de inicio asignado.';
      }
      else
      {
        $answer = '*Módulo de inicio no asignado.';
      }
      return $answer;
    }
    elseif($tipo == 3)
    {
      $sql = "UPDATE Clase SET Modulo_Termino = '{$dos}' WHERE Id = '{$idClase}';";
      if(($mysqli->query($sql)) == true)
      {
        $answer = '*Módulo de término asignado.';
      }
      else
      {
        $answer = '*Módulo de término no asignado.';
      }
      return $answer;
    }
  }

  public function cambiarVacantes($idSeccion,$vacantes)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "UPDATE Seccion SET Vacantes_Utilizadas = '{$vacantes}' WHERE Id = '{$idSeccion}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Vacantes asignadas.';
    }
    else
    {
      $answer = '*Vacantes no asignadas.';
    }
      return $answer;
  }
}

class departamento extends usuario {

  function __construct($nombre,$nombreUsuario,$rut) {
    $this->nombre = $nombre;
    $this->nombreUsuario = $nombreUsuario;
    $this->rut = $rut;
  }

  function __destruct() {
    unset($this->nombre);
    unset($this->nombreUsuario);
    unset($this->rut);
    unset($this);
  }

  public function agregarRamoDepartamento($codigo,$nombre,$tipo,$periodo,$teo,$ayu,$lab,$tall,$cre)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Ramo(Codigo,Nombre,Teoria,Tipo,Periodo,Ayudantia,Laboratorio,Taller,Creditos) VALUES('{$codigo}','{$nombre}','{$teo}','{$tipo}','{$periodo}','{$ayu}','{$lab}','{$tall}','{$cre}')";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Ramo agregado.';
    }
    else
    {
      $answer = '*Ramo no agregado.';
    }
    return $answer;
  }

  public function crearTipoDeRamo($tipo,$abreviacion)
  {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO Ramo_Tipo(Tipo,Abreviacion) VALUES('{$tipo}','{$abreviacion}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Tipo creado.';
    }
    else
    {
      $answer = '*Tipo no creado.';
    }
    return $answer;
  }
}

class JefeDeLaboratorio extends usuario {

  function __construct($nombre,$nombreUsuario,$rut) {
    $this->nombre = $nombre;
    $this->nombreUsuario = $nombreUsuario;
    $this->rut = $rut;
  }

  function __destruct() {
    unset($this->nombre);
    unset($this->nombreUsuario);
    unset($this->rut);
    unset($this);
  }
  
  public function crearLaboratorio($codEdificio,$nSala) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO laboratorio(edificio,sala) VALUES ('{$codEdificio}','{$nSala}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Laboratorio agregado.';
    }
    else
    {
      $answer = '*Laboratorio no agregado.';
    }
    return $answer;
  }
  
   public function modificarLaboratorio($idLab,$codEdificio,$nSala) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "UPDATE laboratorio SET edificio='{$codEdificio}',sala='{$nSala}' WHERE id_lab='{$idLab}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Laboratorio modificado.';
    }
    else
    {
      $answer = '*Laboratorio no modificado.';
    }
    return $answer;
  }
  
  public function eliminarLaboratorio($idLab) { 
	global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "DELETE FROM laboratorio WHERE id_lab = '{$idLab}';";
	if(($mysqli->query($sql)) == true)
    {
      $answer = '*Laboratorio borrado.';
    }
    else
    {
      $answer = '*Laboratorio no borrado.';
    }
    return $answer;
	}
  
  public function listarLaboratorio() {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT id_lab, edificio, sala FROM laboratorio";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($id,$edificio,$sala);
    echo '<table><tr><td>ID</td><td>Edificio</td><td>Sala</td><td>Modificar</td><td>Eliminar</td></tr>';
    $flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$id.'</td><td>'.$edificio.'</td><td>'.$sala.'</td><td><input type="submit" name="modifica" value=" '.$id.' ">Modificar</input></td><td><input type="submit" name="elimina" value=" '.$id.' ">Eliminar</input></td>';
    }
    if($flag == 0)
      echo '<tr><td>No hay laboratorios.</td><td></td><td></td></tr></table>';
    else
      echo '</table>';
    $res->free_result();
  }
  
    public function obtenerSalaLab($idLab) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "SELECT sala FROM laboratorio WHERE id_lab='{$idLab}';";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($dato);
		$flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      	echo $dato;
    }
    if($flag == 0)
      echo '';
    else
      echo '';
    $res->free_result();
	}
	public function obtenerEdificioLab($idLab) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "SELECT edificio FROM laboratorio WHERE id_lab='{$idLab}';";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($dato);
	$flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      	echo $dato;
    }
    if($flag == 0)
      echo '';
    else
      echo '';
    $res->free_result();
	}
  
  public function crearSoftware($nomSoftware,$verSoftware) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "INSERT INTO software(nom_sw,version) VALUES ('{$nomSoftware}','{$verSoftware}');";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Software agregado.';
    }
    else
    {
      $answer = '*Software no agregado.';
    }
    return $answer;
  }
  
  public function modificarSoftware($idSw,$nomSw,$verSw) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "UPDATE software SET nom_sw='{$nomSw}',version='{$verSw}' WHERE id_sw='{$idSw}';";
    if(($mysqli->query($sql)) == true)
    {
      $answer = '*Software modificado.';
    }
    else
    {
      $answer = '*Software no modificado.';
    }
    return $answer;
  }
  
    public function eliminarSoftware($idSw) { 
	global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "DELETE FROM software WHERE id_sw = '{$idSw}';";
	if(($mysqli->query($sql)) == true)
    {
      $answer = '*Software borrado.';
    }
    else
    {
      $answer = '*Software no borrado.';
    }
    return $answer;
	}
  
  public function listarSoftware() {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
    $sql = "SELECT id_sw, nom_sw, version FROM software";
    $res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($id,$nombre,$version);
    echo '<table><tr><td>ID</td><td>Nombre Software</td><td>Versión</td><td>Modificar</td><td>Eliminar</td></tr>';
    $flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$id.'</td><td>'.$nombre.'</td><td>'.$version.'</td><td><input type="submit" name="modifica" value=" '.$id.' ">Modificar</input></td><td><input type="submit" name="elimina" value=" '.$id.' ">Eliminar</input></td>';
    }
    if($flag == 0)
      echo '<tr><td>No hay software.</td><td></td><td></td></tr></table>';
    else
      echo '</table>';
    $res->free_result();
  }
  
  	public function obtenerNombreSw($idSw) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "SELECT nom_sw FROM software WHERE id_sw='{$idSw}';";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($dato);
	$flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      	echo $dato;
    }
    if($flag == 0)
      echo '';
    else
      echo '';
    $res->free_result();
	}
    	public function obtenerVersionSw($idSw) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "SELECT version FROM software WHERE id_sw='{$idSw}';";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($dato);
	$flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      	echo $dato;
    }
    if($flag == 0)
      echo '';
    else
      echo '';
    $res->free_result();
	}
	public function listarAsignaturasUsanLab(){
	global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql="SELECT s.NRC, r.Codigo, s.Numero_Seccion, r.Nombre, s.Id FROM clase AS c, clase_usa_lab AS l, ramo AS r, seccion AS s WHERE c.Id = l.Id AND c.Seccion_Id = s.Id AND s.Codigo_Ramo = r.Codigo ORDER BY s.Numero_Seccion";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($nrc,$codigo,$seccion,$nombre,$idSeccion);
    echo '<table><tr><td>NRC</td><td>Código</td><td>Sección</td><td>Nombre</td><td>Teoría</td><td>Ayudantía</td><td>Laboratorio</td><td>Taller</td><td>Asignar Software</td><td>Asignar Disponibilidad</td></tr>';
    $flag = 0;
	$teoria = 'Teoria';
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      echo '<tr><td>'.$nrc.'</td><td>'.$codigo.'</td><td>'.$seccion.'</td><td>'.$nombre.'</td><td>'.$teoria/*$this->tipoClaseConsulta($idSeccion,$teoria)*/.'</td><td>Ayudantia</td><td>Laboratorio</td><td>Taller</td><td>Software</td><td>Disponibilidad</td></tr>';
    }
    if($flag == 0)
      echo '<tr><td>No hay datos.</td><td></td><td></td></tr></table>';
    else
      echo '</table>';
    $res->free_result();
	}
	public function tipoClaseConsulta($idSec, $tipoClase){
	global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql="SELECT c.Id FROM clase AS c,  seccion AS s WHERE c.Seccion_Id = s.Id AND s.Id = '{$idSec}' AND c.Clase_Tipo = '{$tipoClase}' GROUP BY c.Clase_Tipo";
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($idC);
    $flag = 0;
    while($res->fetch())
    {
      if($flag == 0)
        $flag = 1;
      return 'Existe';
    }
    if($flag == 0)
      return 'No Aplica';

    $res->free_result();
	}
}
/*
  public function agregarAsigNrc($varNRC) {
    global $mysqli,$db_host,$db_user,$db_pass,$db_database;
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_database);
	$sql = "SELECT Id FROM seccion WHERE NRC='{$varNRC}';";	
	$res = $mysqli->prepare($sql);
    $res->execute();
    $res->bind_result($id);
    $flag = 0;
	$count = 0;
	$dato = '';
    while($res->fetch())
    {
	$count = $count + 0;
	$dato = $id;
      if($flag == 0)
        $flag = 1;
    }
    if($flag == 0)
      $answer = '* Asignatura no encontrada.';
    elseif($count > 1)
      $answer = '* Más de un resultado encontrado, comuniquese con el administrador.';
	else{
    $res->free_result();
	$sql2 = "INSERT INTO `clase_usa_lab`(`Id`) VALUES ('{$dato}')";
	if(($mysqli->query($sql2)) == true)
    {
      $answer = '*Software agregado.';
    }
    else
    {
      $answer = '*Software no agregado.';
    }
    }
    return $answer;
	}
	*/


?>
