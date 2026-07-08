FROM python:3.11-slim

WORKDIR /app

# تثبيت الأدوات الأساسية لنظام التشغيل وقاعدة البيانات
RUN apt-get update && apt-get install -y \
    libpq-dev gcc \
    && rm -rf /var/lib/apt/lists/*

# نسخ ملف المكتبات وتثبيتها
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# سكريبت وهمي باش الكونتينر ما يطفاش دابا
CMD ["python", "-c", "import time; print('Python Worker Active...'); time.sleep(3600)"]