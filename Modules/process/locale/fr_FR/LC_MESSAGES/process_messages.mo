��    �        �   �	      H     I     Q     `     h     w          �     �     �     �     �     �     �  2  �  �    �  �  �  �  V   e     �  ]   �  �   9  ]   �  q   Z  6   �  �     >   �  ^   �  �   R  m     m   �  �   �  i   z  i   �  �   N  �   �  �   ]  �   �  �   n   �   �   b   �!  a   R"  �   �"  c   R#  i   �#  "    $  f   C$  �   �$  -   G%  K   u%  f   �%  >   (&  Q   g&  �   �&  #   �'  b   �'  h   	(  h   r(  O   �(  �   +)  �   *  #   �*  M   �*     	+     +     +     !+  3   -+     a+     p+     +  1   �+     �+     �+     �+     �+     ,     
,     ,     ,  �   5,     -      -     %-     *-     /-     <-     C-     H-  	   M-  	   W-     a-     r-     �-     �-     �-     �-     �-     �-     �-     �-  �   .     �.     �.     �.     �.     �.     �.     �.     �.     �.      /     /  
   /     /     -/     C/     P/     _/     g/     s/  �   �/     0     %0     +0     :0  
   H0     S0     a0     s0     �0     �0     �0     �0     �0  	   �0  A   �0  $   
1     /1     A1     N1     \1  #   o1     �1     �1     �1     �1     �1     �1     �1     �1     �1     2     2     2     +2     -2     /2    72     @4     H4     W4     _4     n4     v4     �4     �4     �4     �4     �4     �4     �4  �  �4  �  �8  �  <  P  >  Y   V@  !   �@  h   �@  �   ;A  n   B  �   �B  =   C  �   OC  J   D  d   dD  �   �D  �   �E  �   3F  �   �F  �   fG  �   �G  �   tH  �   I  �   �I  �   nJ  �   K  5  �K  �   �L  �   �M  �   N  g   �N  w   5O  (   �O  �   �O  �   ZP  F   Q  [   VQ  �   �Q  Q   5R  \   �R  �   �R  $   �S  j   �S  u   iT  t   �T  Y   TU  ,  �U  �   �V  >   hW  ^   �W     X     X     X     X  8   4X     mX     |X     �X  E   �X     �X     �X     
Y  	   %Y     /Y     7Y     >Y  !   KY  	  mY     wZ     �Z     �Z     �Z     �Z     �Z     �Z     �Z  	   �Z  	   �Z     �Z     �Z     �Z     [     ![     2[     B[     R[     c[     v[  �   �[     \     \     ,\  &   4\     [\  	   h\     r\     �\     �\     �\     �\  
   �\     �\     �\     �\     �\  
   �\     	]     ]  �   .]     �]  	   �]     �]     �]     ^     )^     7^     I^     W^     e^     ~^     �^     �^  
   �^  W   �^  $   _     +_     A_     Q_     b_  +   u_     �_     �_     �_     �_  $   �_     �_     �_     �_     `     `     ,`  "   .`     Q`     S`     U`     k   �   G   K      J   n   V       �                           �   ]       d   {   c                         3   }   s   T   *   �          &   �       �   ;   )   
                   x   R       f   \   -   g   6   �   ~   _           U           �   7   �         y   o       �   �   	       W       >       0   %   j      N   q   +   E   B          �   P   e   �       |              �   =   Q   !   m      w       h   �   i          X       �   �      �   u         r   F   :   ,      b   �   �                  .      5              �      ^   p                     <   �   M   A   a   z   L   ?   t      �   9   C       �   �   l       �       �   S          '   �   [           �   (      #   H   D   4      $           v   1       Y   `   "      /   2              8       O   �   �         Z   @   I     * feed  * source feed  + feed  + source feed  - feed  - source feed  / feed  / source feed + + input - input / input 1/ source feed <b>Wh Accumulator:</b> Use with emontx, emonth or emonpi pulsecount or an emontx running firmware <i>emonTxV3_4_continuous_kwhtotals</i> sending cumulative watt hours.<br><br>This processor ensures that when the emontx is reset the watt hour count in emoncms does not reset, it also checks filter's out spikes in energy use that are larger than a max power threshold set in the processor, assuming these are error's, the max power threshold is set to 25kW. <br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1 and scale set to 0.001.<br>See forum thread here for an example <a href="https://openenergymonitor.org/emon/node/12308" Creating kWh per day bar graphs from Accumulating kWh </a></p> <p><b>Log to feed:</b> This processor logs to a timeseries feed which can then be used to explore historic data. This is recommended for logging power, temperature, humidity, voltage and current data.</p><p><b>Feed engine:</b><ul><li><b>PHPFina</b> is the recommended feed engine it is a basic fixed interval timeseries engine.</li><li><b>PHPTimeseries</b> is for data posted at a non regular interval such as on state change.</li></ul></p><p><b>Feed interval:</b> When selecting the feed interval select an interval that is the same as, or longer than the update rate that is set in your monitoring equipment. Setting the interval rate to be shorter than the update rate of the equipment causes un-needed disk space to be used up.</p> <p><b>Power to kWh:</b> Convert a power value in Watts to a cumulative kWh feed.<br><br><b>Visualisation tip:</b> Feeds created with this input processor can be used to generate daily kWh data using the BarGraph visualisation with the delta property set to 1.<br>See forum thread here for an example <a href="https://openenergymonitor.org/emon/node/12308">Creating kWh per day bar graphs from Accumulating kWh </a></p> <p><b>Source Feed:</b><br>Virtual feeds should use this processor as the first one in the process list. It sources data from the selected feed.<br>The sourced value is passed back for further processing by the next processor in the processing list.<br>You can then add other processors to apply logic on the passed value for post-processing calculations in realtime.</p><p>Note: This virtual feed process list is executed on visualizations requests that use this virtual feed.</p> <p>Accumulate Wh measurements into kWh/d.<p><b>Input</b>: energy increments in Wh.</p> <p>Add the specified feed.</p> <p>Adds the current value with the last value from a feed as selected from the feed list.</p> <p>Adds the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p> <p>Convert a number that was interpreted as a 16 bit signed number to an unsigned number.</p> <p>Convert a power value in Watts to a feed that contains an entry for the total energy used each day (kWh/d)</p> <p>Convert accumulating kWh to instantaneous power</p> <p>Counts the amount of time that an input is high in each day and logs the result to a feed. Created for counting the number of hours a solar hot water pump is on each day</p> <p>Divide by specified feed. Returns NULL for zero values.</p> <p>Divides the current value by the last value from a feed as selected from the feed list.</p> <p>Divides the current value with the last value from other input as selected from the input list. The result is passed back for further processing by the next processor in the processing list.</p> <p>If value from last process is NOT NULL, process execution will skip execution of next process in list.</p> <p>If value from last process is NOT ZERO, process execution will skip execution of next process in list.</p> <p>If value from last process is NOT equal to the specified value, process execution will skip execution of next process in list.</p> <p>If value from last process is NULL, process execution will skip execution of next process in list.</p> <p>If value from last process is ZERO, process execution will skip execution of next process in list.</p> <p>If value from last process is equal to the specified value, process execution will skip execution of next process in list.</p> <p>If value from last process is greater or equal to the specified value, process execution will skip execution of next process in list.</p> <p>If value from last process is greater than the specified value, process execution will skip execution of next process in list.</p> <p>If value from last process is lower or equal to the specified value, process execution will skip execution of next process in list.</p> <p>If value from last process is lower than the specified value, process execution will skip execution of next process in list.</p> <p>Jumps the process execution to the specified position.</p><p><b>Warning</b><br>If you're not careful you can create a goto loop on the process list.<br>When a loop occurs, the API will appear to lock until the server php times out with an error.</p> <p>Maximal daily value. Upserts on the selected daily feed the highest value reached each day.</p> <p>Minimal daily value. Upserts on the selected daily feed the lowest value reached each day.</p> <p>Multiplies current value by given constant. This can be useful for calibrating a particular variable on the web rather than by reprogramming hardware.</p> <p>Multiplies the current value with the last value from a feed as selected from the feed list.</p> <p>Multiplies the current value with the last value from other input as selected from the input list.</p> <p>Multiply by specified feed.</p> <p>Negative values are zeroed for further processing by the next processor in the processing list.</p> <p>Offset current value by given value. This can again be useful for calibrating a particular variable on the web rather than by reprogramming hardware.</p> <p>Output feed accumulates by input value</p> <p>Output feed is the difference between the current value and the last</p> <p>Positive values are zeroed for further processing by the next processor in the processing list.</p> <p>Publishes value to MQTT topic e.g. 'home/power/kitchen'</p> <p>Return the reciprical of the specified feed. Returns NULL for zero values.</p> <p>Returns the number of pulses incremented since the last update for a input that is a cumulative pulse count. i.e If the input updates from 23400 to 23410 the result will be an incremenet of 10.</p> <p>Subtract the specified feed.</p> <p>Subtracts from the current value the last value from a feed as selected from the feed list.</p> <p>Subtracts from the current value the last value from other input as selected from the input list.</p> <p>The value "0" is passed back for further processing by the next processor in the processing list.</p> <p>The value is set to the original value at the start of the process list.</p> <p>This was automaticaly added when a loop error was discovered on the processList or execution took too many steps to process.  Review the usage of GOTOs or decrease the number of items and delete this entry to resume execution.</p> <p>Updates or inserts daily value on the specified time (given by the JSON time parameter from the API) of the specified feed</p> <p>Upsert kWh to a daily value.</p> <p>Value is set to NULL.</p><p>Useful for conditional process to work on.</p> Accumulator Actions Add Add process Add the HTTP header: "Authorization: Bearer APIKEY" Allow negative Allow positive Apikey authentication Append on the URL of your request: &apikey=APIKEY Arg Available HTML URLs Available JSON commands Calibration Cancel Close Conditional Conditional - User value Creates a histogram of energy binned by power ranges. For each power range on the x-axis, this processor will aggregate the total energy of the stream while it was in that power range.<p><b>Input</b>: power in Watts.</p> Daily Average Data EXIT Edit Edit process Engine Feed GOTO Heat flux Histogram If !=, skip next If !NULL, skip next If !ZERO, skip next If <, skip next If <=, skip next If =, skip next If >, skip next If >=, skip next If NULL, skip next If ZERO, skip next If you want to call any of the following actions when your not logged in you have this options to authenticate with the API key: Input Input on-time Limits List all supported process Log to feed  Main Max daily value Min daily value Misc Not modified Order Phaseshift Power & Energy Power gained to kWh/d Power to kWh Power to kWh/d Process Process API Process actions Processes are executed sequentially with the result value being passed down for further processing to the next processor on this processing list. Publish to MQTT Pulse Rate of change Read & Write: Read only: Reset to NULL Reset to Original Reset to ZERO Schedule Select interval Signed to unsigned Source Feed Text This page To use the json api the request url needs to include <b>.json</b> Total pulse count to pulse increment Type feed name... Type text... Type value... Upsert feed at day Use POST parameter: "apikey=APIKEY" Value Virtual Wh Accumulator Wh increments to kWh/d You have no processes defined d h kWh to Power kWh to kWh/d kWh to kWh/d (OLD) m process list setup s x x input Project-Id-Version: emoncms
Report-Msgid-Bugs-To: 
POT-Creation-Date: 2017-12-17 11:37+0100
PO-Revision-Date: 2017-12-17 11:52+0100
Last-Translator: Aymeric THIBAUT
Language-Team: Baptiste Gaultier (Télécom Bretagne) <baptiste.gaultier@telecom-bretagne.eu>
Language: fr_FR
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Poedit-KeywordsList: _;gettext;gettext_noop
X-Poedit-Basepath: .
X-Poedit-SourceCharset: utf-8
X-Generator: Poedit 2.0.4
X-Poedit-SearchPath-0: ../../..
  * feed  * source feed  + feed  + source feed  - feed  - source feed  / feed  / source feed + + input - input / input 1/ source feed <b>Wh Accumulator:</b>Utilisé avec emontx, emonth ou emonpi pulsecount ou emontx exécutant le micrologiciel <i> emonTxV3_4_continuous_kwhtotals </ i> envoyant des watts cumulatifs.br><br>Ce traitement garantit que lorsque l'emontx est réinitialisé, le nombre de wattheures dans emoncms ne se réinitialise pas, il vérifie également les pics de consommation d'énergie supérieurs au seuil de puissance maximale défini dans le processeur, en supposant qu'il s'agit d'erreurs. Le seuil de puissance maximale est réglé sur 25 kW.<br><br><b>Astuce de visualistion :</b>Les flux créés avec ce traitement d'entrée peuvent être utilisés pour générer des données kWh quotidiennes à l'aide de la visualisation BarGraph avec la propriété delta définie sur 1 et l'échelle définie sur 0,001.<br> Voir le fil du forum pour un exemple <a href="https://openenergymonitor.org/emon/node/12308" Creating kWh per day bar graphs from Accumulating kWh </a></p> <p><b>Log to feed:</b> Ce traitement se connecte à un flux de données temporelles qui peut ensuite être utilisé pour explorer des données historiques. Ceci est recommandé pour l'enregistrement des données de puissance, de température, d'humidité, de tension et de courant.</p><p><b>Feed engine:</b><ul><li><b >PHPFina</b> est le moteur de flux recommandé, c'est un moteur de base de temps à intervalle de temps fixe.</li><li><b>PHPTimeseries</b> est pour les données affichées à un intervalle non régulier, comme lors d'un changement d'état.</li></ul></p><p><b>Feed interval:</b> Lorsque vous sélectionnez l'intervalle du flux, sélectionnez un intervalle identique ou supérieur au taux de mise à jour défini dans votre équipement de surveillance. Un taux d'intervalle inférieur à la fréquence de mise à jour de l'équipement provoque une utilisation de l'espace disque non nécessaire.</p> <p><b>Power to kWh:</b> Convertit une valeur de puissance en Watts en flux kWh cumulatif.<br><br><b>Astuce de visualisation :</b> Les flux créés avec ce traitement d'entrée peuvent être utilisés pour générer des données kWh quotidiennes à l'aide de la visualisation BarGraph avec la propriété delta définie sur 1.<br>Voir le fil du forum ici pour un exemple <a href="https://openenergymonitor.org/emon/node/12308">Creating kWh per day bar graphs from Accumulating kWh </a></p> <p><b>Source Feed:</b><br>Les flux virtuels doivent utiliser ce traitement en tant que premier dans la liste des processus. Il extrait les données du flux sélectionné.<br>La valeur de la source est renvoyée pour un traitement ultérieur par le processeur suivant dans la liste de traitement.<br>Vous pouvez ensuite ajouter d'autres traitements pour appliquer la logique sur la valeur transmise pour les calculs de post-traitement en temps réel.</p><p>Remarque : Cette liste de processus de flux virtuel est exécutée sur les demandes de visualisations qui utilisent ce flux virtuel.</p> <p>Accumule les mesures de Wh en kWh/j.<p><b>Input</b>: incréments d'énergie en Wh.</p> <p>Ajoute le flux spécifié.</p> <p>Ajoute la valeur actuelle à la dernière valeur d'un flux sélectionnée dans la liste des flux.</p> <p>Ajoute la valeur actuelle à la dernière valeur de l'autre entrée sélectionnée dans la liste d'entrée. Le résultat est renvoyé pour traitement ultérieur par le traitement suivant dans la liste de traitement.</p> <p>Convertit un nombre qui a été interprété comme un nombre signé de 16 bits à un nombre non signé.</p> <p>Convertit une valeur de puissance en watts en un flux contenant une entrée pour l'énergie totale utilisée chaque jour (kWh/j)</p> <p>Convertit les kWh accumulés en puissance instantanée</p> <p>Compte la quantité de temps qu'une entrée est élevée chaque jour et enregistre le résultat dans un flux. Créé pour compter le nombre d'heures d'une pompe à eau chaude solaire chaque jour</p> <p>Divise par le flux spécifié. Retourne NULL pur les valeurs zéro.</p> <p>Divise la valeur actuelle la dernière valeur d'un flux sélectionné dans la liste des flux.</p> <p>Divise la valeur actuelle à la dernière valeur de l'autre entrée sélectionnée dans la liste d'entrée. Le résultat est renvoyé pour traitement ultérieur par le traitement suivant dans la liste de traitement.</p> <p>Si la valeur du dernier processus est NOT NULL, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est NOT ZERO, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus n'est PAS égale à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est NULL, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est ZERO, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est égale à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est supérieure ou égale à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est supérieure à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est inferieure ou égale à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Si la valeur du dernier processus est inferieure à la valeur spécifiée, l'exécution du processus ignorera l'exécution du processus suivant dans la liste.</p> <p>Passe l'exécution du processus à la position spécifiée.</p><p><b>Avertissement</b><br>Si vous ne faites pas attention, vous pouvez créer une boucle goto dans la liste des processus.<br>Lorsqu'une boucle se produit, l'API semble se verrouiller jusqu'à ce que le serveur php expire avec une erreur.</p> <p>Valeur quotidienne maximale. Ajoute ou met à jour sur le flux quotidien sélectionné la valeur la plus élevée atteinte chaque jour.</p> <p>Valeur quotidienne minimale. Ajoute ou met à jour sur le flux quotidien sélectionné la valeur la plus élevée atteinte chaque jour</p> <p>Multiplie la valeur actuelle par la constante donnée. Cela peut être utile pour étalonner une variable particulière sur le web plutôt qu'une reprogrammation matérielle.</p> <p>Multiplie la valeur actuelle la dernière valeur d'un flux sélectionné dans la liste des flux.</p> <p>Multiplie la valeur actuelle par la dernière valeur d'une autre entrée sélectionnée dans la liste d'entrée.</p> <p>Multiplie par le flux spécifié.</p> <p>Les valeurs négatives sont mises à zéro pour traitement ultérieur par le traitement suivant dans la liste de traitement.</p> <p>Décale la valeur actuelle d'une valeur donnée. Cela peut encore être utile pour calibrer une variable particulière sur le web plutôt qu'une reprogrammation matérielle.</p> <p>Le flux de sortie s'accumule en fonction de la valeur d'entrée</p> <p>Le flux de sortie est la différence entre la valeur actuelle et la dernière valeur</p> <p>Les valeurs positives sont mises à zéro pour traitement ultérieur par le traitement suivant dans la liste de traitement.</p> <p>Publie la valeur dans un sujet MQTT par exemple 'maison/puissance/cuisine'</p> <p>Renvoie l'inverse du flux spécifié. Renvoie la valeur NULL pour les valeurs nulles.</p> <p>Renvoie le nombre d'incréments d'impulsions depuis la dernière mise à jour pour une entrée qui est un nombre d'impulsions cumulatif. Par exemple, si l'entrée est mise à jour de 23400 à 23410, le résultat sera un incrément de 10.</p> <p>Soustrait le flux spécifié.</p> <p>Soustrait de la valeur actuelle la dernière valeur d'un flux sélectionné dans la liste des flux.</p> <p>Soustrait de la valeur actuelle la dernière valeur de l'autre entrée sélectionnée dans la liste d'entrée.</p> <p>La valeur "0" est renvoyée pour traitement ultérieur par le processeur suivant dans la liste de traitement.</p> <p>La valeur est définie sur la valeur d'origine au début de la liste de processus.</p> <p>Cela a été automatiquement ajouté lorsqu'une erreur de boucle a été découverte dans la liste des traitements ou que l'exécution a pris trop d'étapes à traiter. Vérifiez l'utilisation des GOTO ou réduisez le nombre d'éléments et supprimez cette entrée pour reprendre l'exécution.</p> <p>Met à jour ou insère la valeur quotidienne au temps spécifié (donné par le paramètre de temps JSON de l'API) du flux spécifié</p> <p>Ajoute ou met à jour des kWh à la valeur quotidienne.</p> <p>La valeur est définie à NULL.</p><p>Utile pour un travail sur processus conditionnel.</p> Accumulator Actions Add Ajouter un traitement Ajoutez l'en-tête HTTP : "Authorization: Bearer APIKEY" Allow negative Allow positive Authentification clé API Veuillez ajouter votre clé d'API à la fin de l'url : &apikey=APIKEY Argument URLs HTML disponibles Commandes JSON disponibles Calibrage Annuler Fermer Conditionnel Conditionnel - Valeur utilisateur Crée un histogramme de l'énergie regroupée par plages de puissance. Pour chaque plage de puissance sur l'axe des x, ce traitement va agréger l'énergie totale du flux alors qu'il se trouvait dans cette plage de puissance.<p><b>Input</b>: puissance en Watts.</p> Daily Average Données EXIT Modifier Modifier un traitement Moteur Flux GOTO Heat flux Histogram If !=, skip next If !NULL, skip next If !ZERO, skip next If <, skip next If <=, skip next If =, skip next If >, skip next If >=, skip next If NULL, skip next If ZERO, skip next Si vous souhaitez appeler une des actions suivantes alors que vous n'êtes pas connecté, vous pouvez vous authentifier avec votre clé API : Source Input on-time Limites Lister tous les traitements supportés Log to feed  Principal Max daily value Min daily value Divers Non modifié Ordre Phaseshift Puissance & Énergie Power gained to kWh/d Power to kWh Power to kWh/d Traitement API traitement Actions de traitement Les processus sont exécutés séquentiellement avec la valeur de résultat transmise pour traitement ultérieur au processus suivant sur cette liste de traitement. Publish to MQTT Impulsion Rate of change Lecture & Écriture :  Lecture uniquement :  Reset to NULL Reset to Original Reset to ZERO Planification Sélectionner intervalle Signed to unsigned Source Feed Texte Cette page Si vous souhaitez utiliser l'API json, veuillez ajouter <b>.json</b> à la fin de l'url Total pulse count to pulse increment Saisir nom du flux... Saisir texte... Saisir valeur... Upsert feed at day Utilisez le paramètre POST "apikey=APIKEY" Valeur Virtuel Wh Accumulator Wh increments to kWh/d Vous n'avez aucun traitement défini j h kWh to Power kWh to kWh/d kWh to kWh/d (OLD) m paramétrage liste des traitements s x x input 