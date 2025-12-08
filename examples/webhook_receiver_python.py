"""
Exemple d'implémentation d'un récepteur de webhook avec Flask

pip install flask python-dotenv requests
"""

import os
import json
import hashlib
import hmac
from datetime import datetime
from flask import Flask, request, jsonify
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

# ==================== CONFIGURATION ====================

WEBHOOK_SECRET = os.getenv('WEBHOOK_SECRET')

if not WEBHOOK_SECRET:
    raise ValueError("WEBHOOK_SECRET environment variable not set")

# ==================== UTILITAIRES ====================

def verify_signature(payload: bytes, signature: str) -> bool:
    """
    Vérifier la signature HMAC-SHA256 du webhook
    """
    expected_signature = 'sha256=' + hmac.new(
        WEBHOOK_SECRET.encode(),
        payload,
        hashlib.sha256
    ).hexdigest()

    return hmac.compare_digest(expected_signature, signature)


def calculate_duration(started_at: str, completed_at: str) -> str:
    """
    Calculer la durée entre deux timestamps ISO
    """
    from datetime import datetime
    
    start = datetime.fromisoformat(started_at.replace('Z', '+00:00'))
    end = datetime.fromisoformat(completed_at.replace('Z', '+00:00'))
    
    diff = (end - start).total_seconds()
    minutes = int(diff // 60)
    seconds = int(diff % 60)
    
    return f"{minutes}m {seconds}s"


def handle_quiz_started(data: dict) -> None:
    """
    Gérer l'événement quiz_started
    """
    student = data['student']
    quiz = data['quiz']
    course = data['course']
    started_at = data['startedAt']

    print(f"""
╔══════════════════════════════════════╗
║          📚 QUIZ COMMENCÉ            ║
╠══════════════════════════════════════╣
║ Étudiant: {student['fullName']:<30} ║
║ Email: {student['email']:<35} ║
║ Quiz: {quiz['title']:<35} ║
║ Cours: {course['title']:<34} ║
║ Heure: {started_at}  ║
╚══════════════════════════════════════╝
    """)

    # TODO: Implémenter votre logique métier
    # - Envoyer une notification (email, Slack, Teams, etc.)
    # - Enregistrer en base de données
    # - Mettre à jour un dashboard
    # - Alerter les instructeurs
    # - etc.


def handle_quiz_completed(data: dict) -> None:
    """
    Gérer l'événement quiz_completed
    """
    student = data['student']
    quiz = data['quiz']
    result = data['result']
    
    passed = "✅ RÉUSSI" if result['passed'] else "❌ ÉCHOUÉ"
    percentage = (result['score'] / quiz['totalPoints'] * 100) if quiz['totalPoints'] > 0 else 0
    duration = calculate_duration(result['startedAt'], result['completedAt'])

    print(f"""
╔════════════════════════════════════════╗
║         {passed}  QUIZ COMPLÉTÉ         ║
╠════════════════════════════════════════╣
║ Étudiant: {student['fullName']:<31} ║
║ Email: {student['email']:<36} ║
║ Quiz: {quiz['title']:<36} ║
║ Score: {result['score']}/{quiz['totalPoints']} ({percentage:.1f}%)      ║
║ Durée: {duration:<32} ║
║ Statut: {('RÉUSSI' if result['passed'] else 'ÉCHOUÉ'):<31} ║
╚════════════════════════════════════════╝
    """)

    # TODO: Implémenter votre logique métier
    # - Mettre à jour les grades
    # - Générer un certificat
    # - Envoyer un email
    # - Mettre à jour les statistiques
    # - etc.


# ==================== ROUTES ====================

@app.route('/webhook/quiz', methods=['POST'])
def receive_webhook():
    """
    Route principale pour recevoir les webhooks
    POST /webhook/quiz
    """
    try:
        # 1. Récupérer le payload brut et la signature
        payload = request.get_data()
        signature = request.headers.get('X-Webhook-Signature')
        event_type = request.headers.get('X-Webhook-Event')

        if not signature:
            return jsonify({
                'success': False,
                'error': 'Missing X-Webhook-Signature header'
            }), 401

        # 2. Vérifier la signature
        if not verify_signature(payload, signature):
            return jsonify({
                'success': False,
                'error': 'Invalid signature'
            }), 401

        # 3. Parser le JSON
        try:
            data = json.loads(payload)
        except json.JSONDecodeError:
            return jsonify({
                'success': False,
                'error': 'Invalid JSON payload'
            }), 400

        timestamp = datetime.now().isoformat()
        print(f"\n[{timestamp}] Webhook reçu: {event_type}")

        # 4. Traiter l'événement
        event = data.get('event')

        if event == 'quiz_started':
            handle_quiz_started(data['data'])
        elif event == 'quiz_completed':
            handle_quiz_completed(data['data'])
        else:
            return jsonify({
                'success': False,
                'error': f'Unknown event type: {event}'
            }), 400

        # 5. Répondre avec succès
        return jsonify({
            'success': True,
            'message': 'Event processed successfully',
            'event': event
        }), 200

    except Exception as e:
        print(f"Error processing webhook: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500


@app.route('/health', methods=['GET'])
def health():
    """
    Route de health check
    """
    return jsonify({
        'status': 'healthy',
        'timestamp': datetime.now().isoformat()
    }), 200


@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'error': 'Not found'
    }), 404


# ==================== DÉMARRAGE ====================

if __name__ == '__main__':
    print("""
╔════════════════════════════════════════╗
║   Webhook Receiver lancé sur port 5000 ║
║   URL: http://localhost:5000           ║
║   Endpoint: /webhook/quiz               ║
╚════════════════════════════════════════╝
    """)
    
    app.run(debug=True, host='0.0.0.0', port=5000)


# ==================== EXEMPLES D'INTÉGRATION ====================

"""
1. AVEC NGROK (pour tester localement):
   - pip install ngrok
   - ngrok http 5000
   - Utiliser l'URL générée comme URL du webhook
   - Exemple: https://abc123.ngrok.io/webhook/quiz

2. AVEC WEBHOOK.SITE:
   - Aller sur https://webhook.site
   - Copier l'URL générée
   - L'utiliser comme URL du webhook dans l'admin

3. AVEC GUNICORN (pour production):
   - pip install gunicorn
   - gunicorn -w 4 -b 0.0.0.0:5000 app:app

4. AVEC DOCKER:
   - docker build -t elearning-webhook-receiver .
   - docker run -e WEBHOOK_SECRET=your-secret -p 5000:5000 elearning-webhook-receiver

5. INTÉGRATIONS POSSIBLES:
   - Slack: requests.post(slack_webhook_url, json={...})
   - Email: smtplib.SMTP(...)
   - Database: sqlalchemy.create_engine(...)
   - Fichiers: Enregistrer les événements en JSON
"""

# ==================== EXEMPLE D'INTÉGRATION SLACK ====================

"""
import requests

def notify_slack(event, data):
    slack_webhook_url = os.getenv('SLACK_WEBHOOK_URL')
    
    if not slack_webhook_url:
        return

    student = data['student']
    quiz = data['quiz']

    if event == 'quiz_started':
        color = '#0099ff'
        title = '📚 Quiz commencé'
        fields = [
            {'title': 'Étudiant', 'value': f"{student['fullName']} ({student['email']})", 'short': False},
            {'title': 'Quiz', 'value': quiz['title'], 'short': False},
        ]
    else:
        result = data['result']
        color = '#00ff00' if result['passed'] else '#ff0000'
        title = '✅ Quiz réussi' if result['passed'] else '❌ Quiz échoué'
        fields = [
            {'title': 'Étudiant', 'value': f"{student['fullName']} ({student['email']})", 'short': False},
            {'title': 'Quiz', 'value': quiz['title'], 'short': False},
            {'title': 'Score', 'value': f"{result['score']}/{quiz['totalPoints']}", 'short': True},
        ]

    try:
        requests.post(slack_webhook_url, json={
            'attachments': [{
                'color': color,
                'title': title,
                'fields': fields,
                'ts': int(datetime.now().timestamp())
            }]
        })
    except Exception as e:
        print(f"Error sending Slack notification: {str(e)}")

# Dans handle_quiz_started:
# notify_slack('quiz_started', data)

# Dans handle_quiz_completed:
# notify_slack('quiz_completed', data)
"""
