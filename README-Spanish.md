# Plugin de Prestashop para Reverb
Esto es un app de Prestashop para integrarse con el API de Reverb incluyendo Sincronización de productos (Prestashop->Reverb) y sincronización de ordenes (Reverb ->Prestashop) 
 
Por favor lea este documento completo antes de instalar la aplicación.

## Herramientas
*Crear nuevos anuncios en Reverb desde tus productos en Prestashop, incluyendo sincronización de imágenes y categorías.
*Controla si deseas que el precio/titulo/inventario se sincronice individualmente.
*Sincroniza actualizaciones en tu inventario desde Prestashop a Reverb.
*Sincroniza ordenes desde Reverb a Prestashop
*Sincroniza información de rastreo de un envío desde Prestashop a Reverb
*Los productos se sincronizan como anuncios individuales en Reverb.

## Instalación

Para instalar, busca la ultima versión en la página de “releases” y descarga el archivo `reverb.zip’. Siga las instrucciones de Prestashop para instalaciones manuales aquí: 
[http://www.prestatoolbox.com/content/21-to-install-a-new-prestashop-module](http://www.prestatoolbox.com/content/21-to-install-a-new-prestashop-module).

## Conectándolo a tu cuenta

Visita reverb.com e ingresa a tu cuenta. Luego, navega a tus “account settings” y acceso la página de 'API & Integration’.
 
Genera un nuevo Token de acceso personal. Nómbralo “Prestashop” (o como gustes), y asignarle todos los “oauth scope” al seleccionar cada casilla en esta página. Una vez este generado el token, copialo y pégalo en la sección de log in del modulo Reverb en Prestashop.

## Sincronizar tus productos

### 1.Asegúrate que tus productos tengan códigos SKUs únicos 
### 2. Asigna las categorías de productos

El modulo Reverb te permite seleccionar la categoría del producto y asociarlo con una categoría de Reverb. Puedes encontrar esta opción en la configuración del modulo Reverb dentro de la sección de “Product type mapping”. 

###3. Prepara tus productos

Puedes editar tus productos y activar la sincronización de productos al editar un producto individual (Catalog -> Product Settings -> Click on a product) y dirigiéndote a la sección de “modules”. Luego, haz clic sobre el botón de “configure” para que Reverb acceda la información del producto que será enviado a Reverb. Asegúrate que la sincronización este activada, y rellena cualquier otra información relevante. 

## Habilitar CRON
CRON tasks es un programa que le permite a los usuarios de Unix Systems correr “scripts”, comandos, o software en una fecha determinada o en ciclos preestablecidos. CRON permite sincronizar entre Prestashop y Reverb, para que no tengas que hacer importes manuales de tus productos. 
 
Ve al panel administrativo de tu “hosting” para aprender como habilitar CRON, de tener dificultad, debes preguntarle a tu proveedor como habilitarlo. 
 
Los siguientes Tasks de Cron deben configurarse:
 
`*/5 * * * * php /var/www/html/modules/reverb/cron.php?code=products > /var/log/cron.log`
 
`*/8 * * * * php /var/www/html/modules/reverb/cron.php?code=orders > /var/log/cron.log`
 
El primer cron es un “script” ejecutado cada 5 minutos sobre la sincronización del producto de Prestashop hacia Reverb. El segundo se ejecuta cada 8 minutos sobre la sincronización de ordenes de Reverb a Prestashop.
 
## Manejo de sincronización de producto
 
En la configuration del modulo Reverb en Prestashop, debes acceder al peldaño “Sync Status”. Podrás filtrar tus resultados de búsqueda y ver el estatus de la sincronización (exito, error, etc) mediante un mensaje: “Sync a product manually, a PrestaShop product link, and a Reverb product link.” (Sincorniza un producto manualmente, un link de producto de Prestashop y un link a un producto de Reverb.)

## Preguntas Frecuentes
 
Porque no se me están sincronizando las cosas en tiempo real, o por completo?
 
*Revisa si el Token del API es valido. 
 
*Revisa que cada producto elegido en Reverb esta habilitado correctamente
 
*Revisa los registros en el la sección “Log”
 
Si el problema persiste por favor comunícate con nosotros via integrations@reverb.com


## Additional documentation

Read the **[project documentation][doc-home-fr] in French** for comprehensive information about the requirements, general workflow and installation procedure.

Read the **[project documentation][doc-home-en] in English** for comprehensive information about the requirements, general workflow and installation procedure.

## Recursos
 
*[Full project documentation][doc-home-fr] — Para entendimiento comprensivo del funcionamiento e proceso de instalación en frances.
*[Full project documentation][doc-home-en] Para entendimiento comprensivo del funcionamiento e proceso de instalación en Ingles. 
*[Reverb Support Center][reverb-help] — Para obtener ayuda técnica de Reverb
*[Issues][project-issues] —Para reportar inconvenientes, enviar solicitudes y involucrarse (ver [Apache 2.0 License][project-license])
*[Change log][project-changelog] — Para revisar cambios en la mas reciente version. 
*[Contributing guidelines][project-contributing] — Para contribuir a nuestro source code.

## Licencia 
 
El **reverb.com** disponible debajo del **Apache 2.0 License**. Revisa la  [license file][project-license] para más información.
 
[doc-home-fr]: https://github.com/jprotin/reverb-prestashop/blob/develop/src/reverb/doc/documentation-reverb-fr.md
[doc-home-en]: https://github.com/jprotin/reverb-prestashop/blob/develop/src/reverb/doc/documentation-reverb-fr.md
[reverb-help]: https://reverb.com/fr/page/contact
[project-issues]: https://github.com/jprotin/reverb-prestashop
[project-license]: LICENSE.md
[project-changelog]: CHANGELOG.md
[project-contributing]: CONTRIBUTING.md
