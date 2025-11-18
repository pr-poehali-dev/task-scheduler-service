import json
import os
import psycopg2
from typing import Dict, Any
from urllib.request import Request, urlopen
from urllib.parse import urlencode

def handler(event: Dict[str, Any], context: Any) -> Dict[str, Any]:
    '''
    Business: Telegram webhook handler for task notifications
    Args: event with httpMethod and body containing Telegram update
          context with request_id
    Returns: HTTP response
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
                'statusCode': 500,
                'headers': {'Content-Type': 'application/json'},
                'isBase64Encoded': False,
                'body': json.dumps({'error': 'Bot token not configured'})
            }
        
        update = json.loads(event.get('body', '{}'))
        
        if 'message' in update:
            message = update['message']
            chat_id = message['chat']['id']
            text = message.get('text', '')
            
            conn = psycopg2.connect(os.environ['DATABASE_URL'])
            cur = conn.cursor()
            
            if text.startswith('/start'):
                parts = text.split()
                if len(parts) > 1:
                    email = parts[1]
                    
                    cur.execute(
                        "UPDATE users SET telegram_chat_id = %s WHERE email = %s RETURNING name",
                        (chat_id, email)
                    )
                    result = cur.fetchone()
                    conn.commit()
                    
                    if result:
                        send_telegram_message(bot_token, chat_id, 
                            f"✅ Привет, {result[0]}!\n\n"
                            f"Вы успешно подключили Telegram уведомления.\n"
                            f"Теперь вы будете получать уведомления о новых задачах.")
                    else:
                        send_telegram_message(bot_token, chat_id,
                            "❌ Ошибка: пользователь с таким email не найден.")
                else:
                    send_telegram_message(bot_token, chat_id,
                        "👋 Добро пожаловать в TaskFlow бот!\n\n"
                        "Для подключения уведомлений используйте ссылку из профиля в веб-приложении.")
            
            cur.close()
            conn.close()
        
        elif 'callback_query' in update:
            callback = update['callback_query']
            chat_id = callback['message']['chat']['id']
            message_id = callback['message']['message_id']
            data = callback['data']
            
            if data.startswith('complete_'):
                task_id = int(data.split('_')[1])
                
                conn = psycopg2.connect(os.environ['DATABASE_URL'])
                cur = conn.cursor()
                
                cur.execute(
                    "UPDATE tasks SET completed = TRUE WHERE id = %s RETURNING title",
                    (task_id,)
                )
                result = cur.fetchone()
                conn.commit()
                cur.close()
                conn.close()
                
                if result:
                    edit_telegram_message(bot_token, chat_id, message_id,
                        f"✅ <s>{result[0]}</s>\n\n"
                        f"<b>Статус:</b> Выполнено ✓")
                    
                    answer_callback_query(bot_token, callback['id'], "Задача отмечена выполненной!")
        
        return {
            'statusCode': 200,
            'headers': {'Content-Type': 'application/json'},
            'isBase64Encoded': False,
            'body': json.dumps({'ok': True})
        }
        
    except Exception as e:
        return {
            'statusCode': 500,
            'headers': {'Content-Type': 'application/json'},
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


def edit_telegram_message(token: str, chat_id: int, message_id: int, text: str):
    url = f'https://api.telegram.org/bot{token}/editMessageText'
    data = {
        'chat_id': chat_id,
        'message_id': message_id,
        'text': text,
        'parse_mode': 'HTML'
    }
    req = Request(url, data=json.dumps(data).encode(), headers={'Content-Type': 'application/json'})
    urlopen(req)


def answer_callback_query(token: str, callback_id: str, text: str):
    url = f'https://api.telegram.org/bot{token}/answerCallbackQuery'
    data = {'callback_query_id': callback_id, 'text': text}
    req = Request(url, data=json.dumps(data).encode(), headers={'Content-Type': 'application/json'})
    urlopen(req)
