import json
import os
import psycopg2
from typing import Dict, Any
from urllib.request import Request, urlopen

def handler(event: Dict[str, Any], context: Any) -> Dict[str, Any]:
    '''
    Business: Send task notification to Telegram users
    Args: event with httpMethod POST and body with task details
          context with request_id
    Returns: HTTP response with notification status
    '''
    method: str = event.get('httpMethod', 'POST')
    
    if method == 'OPTIONS':
        return {
            'statusCode': 200,
            'headers': {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': 'POST, OPTIONS',
                'Access-Control-Allow-Headers': 'Content-Type',
                'Access-Control-Max-Age': '86400'
            },
            'body': ''
        }
    
    if method != 'POST':
        return {
            'statusCode': 405,
            'headers': {'Content-Type': 'application/json'},
            'isBase64Encoded': False,
            'body': json.dumps({'error': 'Method not allowed'})
        }
    
    try:
        bot_token = os.environ.get('TELEGRAM_BOT_TOKEN')
        if not bot_token:
            return {
                'statusCode': 200,
                'headers': {'Content-Type': 'application/json'},
                'isBase64Encoded': False,
                'body': json.dumps({'message': 'Bot not configured, skipping notification'})
            }
        
        body_data = json.loads(event.get('body', '{}'))
        task_id = body_data.get('taskId')
        task_title = body_data.get('title')
        deadline = body_data.get('deadline', 'Не указан')
        urgent = body_data.get('urgent', False)
        created_by = body_data.get('createdBy', 'Администратор')
        assigned_to = body_data.get('assignedTo', [])
        
        if not task_id or not task_title or not assigned_to:
            return {
                'statusCode': 400,
                'headers': {'Content-Type': 'application/json'},
                'isBase64Encoded': False,
                'body': json.dumps({'error': 'Missing required fields'})
            }
        
        conn = psycopg2.connect(os.environ['DATABASE_URL'])
        cur = conn.cursor()
        
        notifications_sent = 0
        
        for user_name in assigned_to:
            cur.execute(
                "SELECT telegram_chat_id, email FROM users WHERE name = %s AND telegram_chat_id IS NOT NULL",
                (user_name,)
            )
            result = cur.fetchone()
            
            if result:
                chat_id, email = result
                
                urgent_emoji = '🔥 ' if urgent else ''
                message_text = (
                    f"{urgent_emoji}<b>Новая задача для выполнения</b>\n\n"
                    f"📋 <b>Название:</b> {task_title}\n"
                    f"📅 <b>Срок:</b> {deadline}\n"
                    f"📊 <b>Статус:</b> В работе\n"
                    f"👤 <b>От кого:</b> {created_by}\n"
                )
                
                reply_markup = {
                    'inline_keyboard': [[
                        {'text': '✅ Отметить выполненной', 'callback_data': f'complete_{task_id}'}
                    ]]
                }
                
                send_telegram_message(bot_token, chat_id, message_text, reply_markup)
                notifications_sent += 1
        
        cur.close()
        conn.close()
        
        return {
            'statusCode': 200,
            'headers': {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            'isBase64Encoded': False,
            'body': json.dumps({
                'success': True,
                'notifications_sent': notifications_sent
            })
        }
        
    except Exception as e:
        return {
            'statusCode': 500,
            'headers': {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            'isBase64Encoded': False,
            'body': json.dumps({'error': str(e)})
        }


def send_telegram_message(token: str, chat_id: int, text: str, reply_markup: Dict = None):
    url = f'https://api.telegram.org/bot{token}/sendMessage'
    data = {
        'chat_id': chat_id,
        'text': text,
        'parse_mode': 'HTML'
    }
    if reply_markup:
        data['reply_markup'] = json.dumps(reply_markup)
    
    req = Request(url, data=json.dumps(data).encode(), headers={'Content-Type': 'application/json'})
    urlopen(req)
