#!/bin/bash
# Live smoke test for Sprint 5 RBAC (run against php -S on the RBAC branch).
# Uses the local SQLite fallback DB; cleans up its own users at the end.
# Start the server first: php -S 127.0.0.1:8080 (from the repo root).
set -u
BASE="http://127.0.0.1:8080"
JAR="/tmp/cc-smoke-cookies.txt"
rm -f "$JAR"
PASS=0; FAIL=0
check() { # check <name> <expected-substring> <actual>
    if echo "$3" | grep -qF "$2"; then PASS=$((PASS+1)); echo " [PASS] $1";
    else FAIL=$((FAIL+1)); echo " [FAIL] $1 (missing: $2)"; fi
}
check_not() {
    if echo "$3" | grep -qF "$2"; then FAIL=$((FAIL+1)); echo " [FAIL] $1 (unexpected: $2)";
    else PASS=$((PASS+1)); echo " [PASS] $1"; fi
}
token_from() { # token_from <url> — GET page, extract csrf_token
    curl -s -c "$JAR" -b "$JAR" "$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//'
}
U="smoke_$(date +%s)"
BADGE='<div class="announce-line">'

# --- setup: society admin + plain student via model code ---
php tests/smoke_db.php setup "$U"

# --- 1. login page carries a CSRF token ---
TOK=$(token_from "$BASE/login.php")
[ -n "$TOK" ] && { PASS=$((PASS+1)); echo " [PASS] login page carries csrf_token"; } || { FAIL=$((FAIL+1)); echo " [FAIL] login page carries csrf_token"; }

# --- 2. signup WITHOUT token is rejected ---
OUT=$(curl -s -c "$JAR" -b "$JAR" -d "name=NoToken&email=notoken_$U@nu.edu.pk&password=StrongPass123!" "$BASE/signup.php")
check "signup without CSRF token rejected" "Security token expired" "$OUT"

# --- 3. signup WITH token works ---
TOK=$(token_from "$BASE/signup.php")
curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&name=Smoke+User&email=smoke_new_$U@nu.edu.pk&password=StrongPass123!" "$BASE/signup.php" -o /dev/null -w "%{http_code} %{redirect_url}\n" | grep -q "login.php?registered=1" \
  && { PASS=$((PASS+1)); echo " [PASS] signup with token redirects to login?registered=1"; } \
  || { FAIL=$((FAIL+1)); echo " [FAIL] signup with token redirects to login?registered=1"; }

# --- 4. rate limit: 5 bad logins, then lock message ---
for i in 1 2 3 4 5; do
  TOK=$(token_from "$BASE/login.php")
  curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&email=smoke_new_$U@nu.edu.pk&password=WrongPass123!" "$BASE/login.php" -o /dev/null
done
TOK=$(token_from "$BASE/login.php")
OUT=$(curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&email=smoke_new_$U@nu.edu.pk&password=WrongPass123!" "$BASE/login.php")
check "6th failed login shows lockout message" "Too many failed login attempts" "$OUT"
php tests/smoke_db.php clearlock "smoke_new_$U@nu.edu.pk"

# --- 5. login as student, create post WITHOUT token -> rejected; WITH token -> created ---
TOK=$(token_from "$BASE/login.php")
curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&email=smoke_new_$U@nu.edu.pk&password=StrongPass123!" "$BASE/login.php" -o /dev/null -w "%{redirect_url}\n" | grep -q "index.php" \
  && { PASS=$((PASS+1)); echo " [PASS] student login redirects to index"; } \
  || { FAIL=$((FAIL+1)); echo " [FAIL] student login redirects to index"; }
curl -s -b "$JAR" -d "action=create&content=No+token+post" "$BASE/post_action.php" -o /dev/null
PAGE=$(curl -s -b "$JAR" "$BASE/index.php")
check "post without CSRF token rejected" "Security token expired" "$PAGE"
check_not "token-less post was not created" "No token post" "$PAGE"
TOK=$(token_from "$BASE/index.php")
curl -s -b "$JAR" -d "csrf_token=$TOK&action=create&content=Smoke+post+$U&is_announcement=1" "$BASE/post_action.php" -o /dev/null
PAGE=$(curl -s -b "$JAR" "$BASE/index.php")
check "student post created" "Smoke post $U" "$PAGE"
check_not "student post is NOT flagged as announcement" "$BADGE" "$PAGE"
check_not "student sees no announcement checkbox" "Post as announcement" "$PAGE"
check_not "student sees no Admin link" "admin.php" "$PAGE"

# --- 6. student cannot open admin panel ---
curl -s -b "$JAR" "$BASE/admin.php" -o /dev/null -w "%{redirect_url}\n" | grep -q "index.php" \
  && { PASS=$((PASS+1)); echo " [PASS] student admin.php redirects to index"; } \
  || { FAIL=$((FAIL+1)); echo " [FAIL] student admin.php redirects to index"; }

# --- 7. login as society admin: announcement checkbox + announcement badge ---
rm -f "$JAR"
TOK=$(token_from "$BASE/login.php")
curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&email=smoke_soc_$U@nu.edu.pk&password=StrongPass123!" "$BASE/login.php" -o /dev/null
PAGE=$(curl -s -b "$JAR" "$BASE/index.php")
check "society admin sees announcement checkbox" "Post as announcement" "$PAGE"
TOK=$(token_from "$BASE/index.php")
curl -s -b "$JAR" -d "csrf_token=$TOK&action=create&content=Society+announcement+$U&is_announcement=1" "$BASE/post_action.php" -o /dev/null
PAGE=$(curl -s -b "$JAR" "$BASE/index.php")
check "society announcement created with badge" "$BADGE" "$PAGE"

# --- 8. promote the student to society_admin via admin panel (as system admin) ---
# Make the society account a system admin first (direct DB, simulates seed admin).
SOC_ID=$(php tests/smoke_db.php getid "smoke_soc_$U@nu.edu.pk")
php tests/smoke_db.php setrole "$SOC_ID" system_admin > /dev/null
rm -f "$JAR"
TOK=$(token_from "$BASE/login.php")
curl -s -c "$JAR" -b "$JAR" -d "csrf_token=$TOK&email=smoke_soc_$U@nu.edu.pk&password=StrongPass123!" "$BASE/login.php" -o /dev/null
PAGE=$(curl -s -b "$JAR" "$BASE/admin.php")
check "system admin opens admin panel" "Admin Panel" "$PAGE"
check "admin panel lists the smoke student" "smoke_stu_$U@nu.edu.pk" "$PAGE"
STU_ID=$(php tests/smoke_db.php getid "smoke_stu_$U@nu.edu.pk")
TOK=$(token_from "$BASE/admin.php")
curl -s -b "$JAR" -d "csrf_token=$TOK&action=assign_role&user_id=$STU_ID&role=society_admin" "$BASE/admin_action.php" -o /dev/null
ROLE=$(php tests/smoke_db.php getrole "$STU_ID")
[ "$ROLE" = "society_admin" ] && { PASS=$((PASS+1)); echo " [PASS] role assignment persisted (society_admin)"; } || { FAIL=$((FAIL+1)); echo " [FAIL] role assignment persisted (got $ROLE)"; }
# self-role-change is blocked
TOK=$(token_from "$BASE/admin.php")
curl -s -b "$JAR" -d "csrf_token=$TOK&action=assign_role&user_id=$SOC_ID&role=student" "$BASE/admin_action.php" -o /dev/null
ROLE=$(php tests/smoke_db.php getrole "$SOC_ID")
[ "$ROLE" = "system_admin" ] && { PASS=$((PASS+1)); echo " [PASS] self role change blocked"; } || { FAIL=$((FAIL+1)); echo " [FAIL] self role change blocked (got $ROLE)"; }

# --- 9. AJAX like without token -> 403; with token -> success ---
TOK=$(token_from "$BASE/index.php")
POST_ID=$(php tests/smoke_db.php getpostid "Smoke post $U")
OUT=$(curl -s -b "$JAR" -d "post_id=$POST_ID" "$BASE/like_action.php")
check "like without token -> 403" "Security token expired" "$OUT"
OUT=$(curl -s -b "$JAR" -d "post_id=$POST_ID&csrf_token=$TOK" "$BASE/like_action.php")
check "like with token succeeds" '"success":true' "$OUT"

# --- cleanup ---
php tests/smoke_db.php cleanup "$U"

echo "================================================="
echo "SMOKE TEST: $PASS passed, $FAIL failed"
echo "================================================="
[ "$FAIL" -eq 0 ]
