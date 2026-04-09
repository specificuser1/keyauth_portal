# Railway CLI install karen (agar nahi hai)
npm i -g @railway/cli

# Project directory mein ja kar link karen
railway link

# Local SQL file import karen
railway run "mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE" < sql/schema.sql
