**************Multiple Image Splicing Dataset (MISD): A Dataset for Multiple Splicing   ************


****************
* INTRODUCTION *
****************
The goal of this dataset is to provide a benchmark set that people can compare algorithms on. It was created for trustworthy/tampered image detection, but its use is not just limited to this application.

In this dataset, there are 3 directories: Au ,Sp and Ground Truth Masks.

Au containts authentic images, and Sp contains Multiple Spliced Images. 

In Au , there are 618 images, and in Sp, there are 300. The image size is 9571 × 15022. The Multiple Spliced Images are created using the authentic images.

The Authentic images are consistes of different categories namely animal,architecture,art,character,indoor,nature,scene,plant and texture of .jpg format.

Animal       - 167
Architecture - 35
Art          - 76
Character    - 124
Indoor       - 07
Nature       - 53
Plant        - 50
Scene        - 74
Texture      - 32

Total 				-     618.jpg files

************************
* FILENAME CONVENTIONS *
************************

The filename conventions are as follows:

---- AUTHENTIC IMAGES ----

(Au)_(category)_(index).jpg

There are 8 categories :  nat: nature,arc: architecture,art: art,cha: characters,ind: indoor,pla: plants,sec :scene,txt : texture

---- SPLICED IMAGES ----

Sp_D_the source image ID_the target image ID_the target image ID_(as many images as you want to splice)_Multiple Spliced Image ID.png

Sp folder contains total 300 Multiple Spliced Images with all these categories.


************************
* GROUND TRUTH MASKS *
************************
Under this directory there is seperate folder for each Multiple Spliced Image . Under each Multiple Spliced Image , there are two subfolders namely images and masks. images folder conatins Multiple Spliced Image.masks folder contain ground truth masks for spliced object from various authentic images.The ground truth masks are generated using Python Script. 



*********************





