
cp -r /data/html/Prod/KBCommons_multi/resources/views/system/tools/MViz /home/chanye/projects/

mkdir -p /home/chanye/projects/MViz/controller
mkdir -p /home/chanye/projects/MViz/routes

cp -r /data/html/Prod/KBCommons_multi/app/Http/Controllers/System/Tools/KBCToolsMVizController.php /home/chanye/projects/MViz/controller/

cp -r /data/html/Prod/KBCommons_multi/public/system/home/MViz/* /home/chanye/projects/MViz/

grep "MViz" /data/html/Prod/KBCommons_multi/routes/web.php > /home/chanye/projects/MViz/routes/web.php
