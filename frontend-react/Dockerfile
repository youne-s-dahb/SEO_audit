FROM node:20-alpine

WORKDIR /app

# نسخ ملفات الحزم أولاً لتسريع الـ Build
COPY package*.json ./
RUN npm install

# نسخ باقي ملفات المشروع
COPY . .

# الباب الافتراضي لـ Vite
EXPOSE 5173

# تشغيل السيرفر مع خاصية --host باش يخترق الكونتينر وتقدر تشوفو فوندوز
CMD ["npm", "run", "dev", "--", "--host"]