#!/usr/bin/env python3
"""
Exemple d'implémentation Webhook en Python avec Flask
Pour recevoir et traiter les notifications de quiz
"""

from flask import Flask, request, jsonify
import hmac
import hashlib
import json
from datetime import datetime
from typing import Dict, Any

app = Flask(__name__)

# Configuration
WEBHOOK_SECRET = 'votre_secret_webhook_ici'
PORT = 3000

def verify_webhook_signature(signature: str, payload: bytes) -> bool:
    """
    Vérifier la signature HMAC-SHA256 du webhook
    """
    expected_signature = 'sha256=' + hmac.new(
        WEBHOOK_SECRET.encode(),
        payload,
        hashlib.sha256
    ).hexdigest()

    # Vérification sûre contre les attaques par timing
    return hmac.compare_digest(signature, expected_signature)


@app.before_request
def verify_webhook():
    """
    Middleware pour vérifier les webhooks
    """
    if request.method != 'POST' or not request.path.startswith('/webhook'):
        return

    signature = request.headers.get('X-Webhook-Signature')
    event = request.headers.get('X-Webhook-Event')

    if not signature:
        return jsonify({'error': 'Missing X-Webhook-Signature header'}), 400

    # Récupérer le payload brut
    payload = request.get_data()

    # Vérifier la signature
    if not verify_webhook_signature(signature, payload):
        print('❌ Signature webhook invalide')
        return jsonify({'error': 'Invalid signature'}), 401

    # Passer les informations de vérification à la route
    request.webhook_event = event
    request.webhook_data = json.loads(payload)


@app.route('/webhook/quiz', methods=['POST'])
def receive_webhook():
    """
    Route pour recevoir les webhooks de quiz
    """
    event = request.webhook_event
    data = request.webhook_data

    print(f'\n✅ Webhook reçu: {event}')
    print(f'📅 Timestamp: {data.get("timestamp")}')

    if event == 'quiz_started':
        handle_quiz_started(data.get('data', {}))
    elif event == 'quiz_completed':
        handle_quiz_completed(data.get('data', {}))

    return jsonify({
        'success': True,
        'message': 'Webhook processed successfully',
        'processed_at': datetime.now().isoformat()
    }), 200


def handle_quiz_started(data: Dict[str, Any]) -> None:
    """
    Traiter un quiz commencé
    """
    student = data.get('student', {})
    quiz = data.get('quiz', {})
    course = data.get('course', {})

    print(f"""
📚 QUIZ COMMENCÉ
{'=' * 50}
Étudiant: {student.get('fullName')}
Email: {student.get('email')}

Cours: {course.get('title')}
Quiz: {quiz.get('title')}
Points: {quiz.get('totalPoints')}
Temps limite: {quiz.get('timeLimit')}s ({quiz.get('timeLimit', 0) // 60} min)

Commencé à: {data.get('startedAt')}
{'=' * 50}
    """)

    # Exemple: Envoyer une notification
    send_email_notification(
        'admin@elearning.com',
        f"{student.get('fullName')} a commencé le quiz: {quiz.get('title')}",
        data
    )

    # Exemple: Enregistrer l'activité
    log_quiz_activity('quiz_started', data)

    # Exemple: Envoyer une alerte Slack
    send_slack_notification(
        f":thinking_face: {student.get('fullName')} a commencé le quiz **{quiz.get('title')}**"
    )


def handle_quiz_completed(data: Dict[str, Any]) -> None:
    """
    Traiter un quiz complété
    """
    student = data.get('student', {})
    quiz = data.get('quiz', {})
    course = data.get('course', {})
    result = data.get('result', {})

    total_points = quiz.get('totalPoints', 1)
    score = result.get('score', 0)
    score_percentage = (score / total_points * 100) if total_points > 0 else 0
    passed = result.get('passed', False)

    print(f"""
🎉 QUIZ COMPLÉTÉ
{'=' * 50}
Étudiant: {student.get('fullName')}
Email: {student.get('email')}

Cours: {course.get('title')}
Quiz: {quiz.get('title')}

Score: {score}/{total_points} ({score_percentage:.2f}%)
Résultat: {'✅ RÉUSSI' if passed else '❌ ÉCHOUÉ'}

Durée: {calculate_duration(result.get('startedAt'), result.get('completedAt'))}s
{'=' * 50}
    """)

    # Exemple: Envoyer une notification
    subject = (
        f"{student.get('fullName')} a réussi le quiz: {quiz.get('title')}"
        if passed else
        f"{student.get('fullName')} n'a pas réussi le quiz: {quiz.get('title')}"
    )
    send_email_notification('admin@elearning.com', subject, data)

    # Exemple: Mettre à jour les statistiques
    update_student_stats(student.get('id'), result)

    # Exemple: Envoyer une alerte Slack
    emoji = ':tada:' if passed else ':warning:'
    send_slack_notification(
        f"{emoji} {student.get('fullName')} a complété **{quiz.get('title')}** "
        f"avec {score_percentage:.2f}%"
    )


def calculate_duration(started_at: str, completed_at: str) -> int:
    """
    Calculer la durée entre deux timestamps
    """
    try:
        start = datetime.fromisoformat(started_at.replace('Z', '+00:00'))
        end = datetime.fromisoformat(completed_at.replace('Z', '+00:00'))
        return int((end - start).total_seconds())
    except (ValueError, TypeError):
        return 0


def send_email_notification(to: str, subject: str, data: Dict[str, Any]) -> None:
    """
    Envoyer une notification par email
    """
    print(f'📧 Email envoyé à {to}: {subject}')
    # TODO: Implémenter avec smtplib, sendgrid, etc.


def log_quiz_activity(activity_type: str, data: Dict[str, Any]) -> None:
    """
    Enregistrer l'activité du quiz
    """
    print(f'📝 Activité enregistrée: {activity_type}')
    # TODO: Implémenter la sauvegarde en base de données


def send_slack_notification(message: str) -> None:
    """
    Envoyer une notification Slack
    """
    print(f'💬 Slack: {message}')
    # TODO: Implémenter avec requests vers l'API Slack


def update_student_stats(student_id: int, result: Dict[str, Any]) -> None:
    """
    Mettre à jour les statistiques de l'étudiant
    """
    print(f'📊 Statistiques mises à jour pour l\'étudiant {student_id}')
    # TODO: Implémenter la mise à jour des stats


@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Not found'}), 404


@app.errorhandler(500)
def internal_error(error):
    print(f'Erreur serveur: {error}')
    return jsonify({'error': 'Internal server error'}), 500


if __name__ == '__main__':
    print(f'\n🚀 Serveur webhook écoutant sur le port {PORT}')
    print(f'📍 URL: http://localhost:{PORT}/webhook/quiz')
    print(f'🔐 Secret HMAC configuré: {WEBHOOK_SECRET}\n')

    app.run(
        host='0.0.0.0',
        port=PORT,
        debug=True
    )
