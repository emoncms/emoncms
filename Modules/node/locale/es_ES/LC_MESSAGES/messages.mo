��    >        S   �      H     I     e     m     q     ~     �     �     �     �     �  	   �  .   �     �               ;  ,   Y     �  �   �  �        �     �  (   �     �     �  
             &     7     <     E     S     Y     \  '   b     �     �     �     �  
   �     �     �     �  .   �     	  q  	  {   �
  D        Q     X     f     �     �  	   �     �     �     �     �     �     �  	   �  �  �  )   �     �     �     �     �  	   	          *     3     9     U  /   d     �     �     �     �  %   �       �   &  �   �     p     w     �     �     �     �     �     �     �       $   "     G     M     P  )   V     �     �     �     �     �     �     �     �  '   �       }  +  �   �  ~   0  	   �     �  #   �     �               %     .  
   3     >     @     I  	   N           :                                *   .   2       /      
   ;   <   (   0             9      >   5   3       ,       -   6   )      =       '   $      "   !   7      %   4                        +                        #                 1             8                 	                &       (Click to select a decoder) Actions Add Add process: Apikey authentication Arg Battery Voltage Config Create Custom decoder Datatype: EmonTx V3 (Continuous sampling with Wh totals) External temperature Feed engine Fixed Interval No Averaging Fixed Interval With Averaging GRAPHITE (Requires installation of graphite) Humidity If you want to call any of the following actions when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY. Input processes are executed sequentially with the result being passed back for further processing by the next processor in the input processing list. Integer Internal temperature MYSQL (Slow when there is a lot of data) Message Number Name: No decoder No nodes detected yet No of variables: Node Node API Node API Help Nodes Ok Order PHPTIMESTORE (Port of timestore to PHP) Posting data Process Raw byte data: Read & Write: Read only: Save Scale: Select interval TIMESTORE (Requires installation of timestore) Temperature The node module accepts a comma seperated string of byte (0-256) values as generated by the rfm12pi adapter board running the RFM12Demo sketch written by Jean Claude Wippler of jeelabs. This byte value string is then decoded by the node module according to the decoder selected into the variables that where packaged up using the struct definitions on the sensor nodes. This is an alternative entry point to inputs designed around providing flexible decoding of RF12b struct based data packets To use this module send a byte value csv string and the node id to:  Units: Unsigned long Variable Interval No Averaging With write apikey: hour hours ago inactive mins mins ago s s ago temp variable: Project-Id-Version: emoncms
POT-Creation-Date: 2015-02-11 15:58+0100
PO-Revision-Date: 2015-02-11 16:03+0100
Last-Translator: Jesús Jiménez <jesjimenez@gmail.com>
Language-Team: 
Language: es_ES
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Generator: Poedit 1.5.4
X-Poedit-Basepath: .
Plural-Forms: nplurals=2; plural=(n != 1);
X-Poedit-KeywordsList: _;gettext;gettext_noop
X-Poedit-SearchPath-0: ../../..
 (Pulse para seleccionar un decodificador) Acciones Añadir Añadir proceso: Autenticación por clave de API Argumento Voltaje de la batería Opciones Crear Decodificador personalizado Tipo de datos: EmonTx V3 (Muestreo continuo con totales de Wh) Temperatura externa Motor de fuente Intervalos fijos, sin promedios Intervalos fijos, con promedios GRAPHITE (Requiere instalar graphite) Humedad Para ejecutar alguna de las siguientes acciones cuando no hay una sesión iniciada, es necesario añadir la clave de API (apikey) a la URL de la petición: &apikey=APIKEY. Los procesos de entrada se ejecutan secuencialmente. La salida de cada proceso pasa al siguiente, en el orden indicado en la lista de procesos de la entrada. Entero Temperatura interna MYSQL (lento con muchos datos) Nº de mensaje Nombre: Sin decodificador No se han detectado nodos Nº de variables: Nodo API para la gestión de nodos Ayuda de la API de gestión de nodos Nodos Ok Orden PHPTIMESTORE (Port de timestore para PHP) Enviando información Proceso Datos en bruto: Lectura y escritura: Sólo lectura: Guardar Escala: Seleccione intervalo TIMESTORE (requiere instalar timestore) Temperatura El módulo de nodo acepta una cadena de bytes (0-256) separados por comas, iguales a los generados por la placa rfm12pi ejecutando el sketch RFM12Demo escrito por Jean Claude Wippler, de jeelabs. La cadena de valores se decodifica por el módulo de acuerdo al decodificador escogido en las variables que fueron incluidas usando las definiciones de estructura de los nodos sensores. Este es un punto de entrada alternativo diseñado para proporcionar una decodificación flexible de paquetes de datos basados en RF12b Para usar este módulo envía una lista de valores separados por comas y el id de nodo usando una URL similar a la siguiente:  Unidades: Entero largo sin signo Intervalos variables, sin promedios Con la clave API de escritura: hora horas atrás inactivo mins min atrás s s atrás temp variable: 