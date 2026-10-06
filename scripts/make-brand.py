import os
import base64
import io
import numpy as np
from PIL import Image

brand_dir = r'C:\wamp64\www\glutarepubliq\public\media\brand'
public_dir = r'C:\wamp64\www\glutarepubliq\public'
os.makedirs(brand_dir, exist_ok=True)

src = Image.open(r'C:\Users\Nick\Downloads\glutarepubliq\395209878_849678756599097_2119227777378851255_n.jpg').convert('RGBA')
crop = src.crop((225, 210, 735, 720))

arr = np.array(crop, dtype=np.float32)
dist = np.sqrt((255 - arr[:,:,0])**2 + (255 - arr[:,:,1])**2 + (255 - arr[:,:,2])**2)
alpha = np.clip(dist * 9.0, 0, 255)
arr[:,:,3] = alpha
transparent_logo = Image.fromarray(arr.astype(np.uint8))

logo_256 = transparent_logo.resize((256, 256), Image.Resampling.LANCZOS)
logo_256.save(os.path.join(brand_dir, 'logo.png'))
logo_256.save(os.path.join(brand_dir, 'logo.webp'), 'WEBP', quality=95)

logo_512 = transparent_logo.resize((512, 512), Image.Resampling.LANCZOS)
logo_512.save(os.path.join(brand_dir, 'logo-512.png'))
logo_512.save(os.path.join(brand_dir, 'logo-512.webp'), 'WEBP', quality=95)

arr_light = np.array(transparent_logo, dtype=np.float32)
is_dark_text = (arr_light[:,:,0] < 90) & (arr_light[:,:,1] < 120) & (arr_light[:,:,2] < 150) & (arr_light[:,:,3] > 40)
arr_light[is_dark_text, 0] = 255
arr_light[is_dark_text, 1] = 255
arr_light[is_dark_text, 2] = 255
light_logo = Image.fromarray(arr_light.astype(np.uint8))
light_256 = light_logo.resize((256, 256), Image.Resampling.LANCZOS)
light_256.save(os.path.join(brand_dir, 'logo-light.png'))
light_256.save(os.path.join(brand_dir, 'logo-light.webp'), 'WEBP', quality=95)

ico_img = transparent_logo.resize((48, 48), Image.Resampling.LANCZOS)
ico_img.save(os.path.join(public_dir, 'favicon.ico'), format='ICO', sizes=[(16,16), (32,32), (48,48)])

touch = Image.new('RGBA', (180, 180), (247, 250, 250, 255))
touch_mark = transparent_logo.resize((140, 140), Image.Resampling.LANCZOS)
touch.paste(touch_mark, (20, 20), touch_mark)
touch.convert('RGB').save(os.path.join(public_dir, 'apple-touch-icon.png'))

b = io.BytesIO()
transparent_logo.resize((64, 64), Image.Resampling.LANCZOS).save(b, format='PNG')
encoded = base64.b64encode(b.getvalue()).decode('ascii')
svg_content = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
<image width="64" height="64" href="data:image/png;base64,{encoded}"/>
</svg>'''
with open(os.path.join(public_dir, 'favicon.svg'), 'w', encoding='utf-8') as f:
    f.write(svg_content)

print("Brand and logo assets generated successfully!")
